# Gitea CI/CD

## What is already in the repository

`quality.yml` runs for pushes, pull requests, and manual starts on `main` and
`develop`. The PHP 8.4 job checks every PHP file with `php -l`, runs
`php tests/run.php`, PHPStan and Composer audit, then rejects tracked local
secrets and whitespace errors. It uses the `ubuntu-latest` runner label, then
runs in the official `php:8.4-cli-bookworm` container. This matches the
production PHP-FPM 8.4 version.

The separate PHP 8.5 job repeats syntax checks, unit tests, PHPStan and the
Composer audit. It is a compatibility warning system for the future PHP
upgrade and does not alter the production runtime.

The same workflow starts an isolated `mysql:8.4` service with the temporary
`tori_integration_test` database. It runs workday, pause, remote-work,
presence, business-trip reminder and remember-token scenarios, then applies
every classified migration with `scripts/ci/check-sql-migrations.sh`.
The job has no production or stage database credentials and the service is
discarded after the workflow finishes.

After the three local checks succeed, the optional `stage-smoke` job runs on
the same `ubuntu-latest` runner. It only requests the protected `health.php`
endpoint and the authorization page of the test stand. It never deploys code,
changes Nginx or PHP-FPM, writes to the application database, or runs SQL.
Until the required repository secrets are added, this job completes with a
clear skip message and the rest of CI remains fully usable.

`stage-deploy.yml` is manual only. It repeats the checks and deploys a release
to the test stand using `scripts/deploy-stage-release.sh`. It does not change a
database or execute SQL files. Database migrations must remain a reviewed,
separate step until integration tests are configured.

## Gitea setup

1. Check the Gitea version. Gitea Actions requires Gitea 1.19 or later. For
   versions earlier than 1.21, add this to `/etc/gitea/app.ini` and restart
   Gitea:

   ```ini
   [actions]
   ENABLED=true
   ```

2. In the repository settings, enable `Repository Actions`.

3. The existing global `proxmox-ubuntu` runner must be online and expose the
   `ubuntu-latest` label. Docker must be available to that runner so it can
   start the PHP and temporary MySQL containers.

4. Push this configuration to Gitea. The first push will automatically start
   one PHP 8.4 check in the `Actions` tab. The job runs in the runner's
   temporary Debian container and does not access the application database,
   Nginx, PHP-FPM, or project deployment directories.

5. The runner will download the official PHP 8.4 image from Docker Hub on its
   first run. Later jobs reuse the local Docker image cache.

6. To enable the read-only test-stand check, add these **repository Action
   secrets** in `Settings -> Actions -> Secrets`:

   ```text
   TORI_STAGE_SMOKE_URL=http://192.168.100.216:8080
   TORI_STAGE_HEALTH_TOKEN=<same value as HEALTH_CHECK_TOKEN in the stage .env>
   ```

   `TORI_STAGE_HEALTH_URL` is optional and is needed only when `health.php` is
   published at a different URL. No secret gives the workflow access to the
   production server.

## Test-stand deployment

Use a separate runner with the label `tori-stage-deploy:host` on the test
server, under an unprivileged deployment account that can write only to the
stage release directory. Give its service environment these values:

```text
TORI_STAGE_RELEASES_DIR=/var/www/tori-stage-releases
TORI_STAGE_CURRENT_LINK=/var/www/tori-stage-current
TORI_STAGE_SHARED_ENV=/etc/tori-stage/.env
TORI_STAGE_HEALTH_URL=http://127.0.0.1:8080/health.php
TORI_STAGE_HEALTH_TOKEN=<same value as HEALTH_CHECK_TOKEN in the stage .env>
```

Point the test Nginx virtual host to `/var/www/tori-stage-current`. The deploy
workflow copies the checked revision to a new release directory, attaches the
server-only `.env`, switches the symlink, and checks PHP plus MySQL through the
supplied URL. It keeps previous release directories for fast manual rollback.

Add a long random value to the server-only stage `.env`:

```text
HEALTH_CHECK_TOKEN=<long random value>
```

The token is sent only by the deployment runner as the `X-Tori-Health-Token`
header. Requests without it receive `404` and cannot use `health.php` to probe
the application.

The stage runner must be dedicated to this private repository. Protect `main`
and `develop` in Gitea so unreviewed code cannot reach it.

## Manual smoke check

After a manual deployment to the test stand, run the following command on a
host that can reach the stand. It checks the protected health endpoint, its
database connection, and that the authorization page returns `200`. It does not
write to the application or database.

```bash
export TORI_STAGE_HEALTH_TOKEN='<HEALTH_CHECK_TOKEN from the stage .env>'
bash scripts/smoke-stage.sh http://192.168.100.216:8080
```

## Database migrations

Versioned schema migrations are in `sql/migrations`. The CI job validates the
catalog and applies it only to the disposable MySQL test database. The manual
scripts in `sql/` that repair or inspect operational data are deliberately not
run automatically. See [the migration guide](../sql/migrations/README.md) for
the dry-run and explicit-confirmation commands.

## Further work

1. Extend PHPStan from the current level 5 service scope to legacy report and
   directory modules after their database connection handling is made explicit.
2. Keep production deployment manual: only after the test stand is approved,
   run a dedicated production workflow from `main`.
