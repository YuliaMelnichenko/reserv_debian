# Gitea CI/CD

## What is already in the repository

`quality.yml` runs for pushes, pull requests, and manual starts on `main` and
`develop`. It checks every PHP file with `php -l`, runs `php tests/run.php`,
and rejects whitespace errors. It uses the existing `debian-12` runner label
to select the runner, then runs in the official `php:8.4-cli-bookworm`
container. This matches the production PHP-FPM 8.4 version.

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

3. The existing global `proxmox-debian` runner must be online and expose the
   `debian-12` label. No extra runner, registration token, Docker image, or
   host-level package is required for the quality workflow.

4. Push this configuration to Gitea. The first push will automatically start
   one PHP 8.4 check in the `Actions` tab. The job runs in the runner's
   temporary Debian container and does not access the application database,
   Nginx, PHP-FPM, or project deployment directories.

5. The runner will download the official PHP 8.4 image from Docker Hub on its
   first run. Later jobs reuse the local Docker image cache.

## Test-stand deployment

Use a separate runner with the label `tori-stage-deploy:host` on the test
server, under an unprivileged deployment account that can write only to the
stage release directory. Give its service environment these values:

```text
TORI_STAGE_RELEASES_DIR=/var/www/tori-stage-releases
TORI_STAGE_CURRENT_LINK=/var/www/tori-stage-current
TORI_STAGE_SHARED_ENV=/etc/tori-stage/.env
TORI_STAGE_HEALTH_URL=http://127.0.0.1:8080/auth.php
```

Point the test Nginx virtual host to `/var/www/tori-stage-current`. The deploy
workflow copies the checked revision to a new release directory, attaches the
server-only `.env`, switches the symlink, and checks the supplied URL. It keeps
previous release directories for fast manual rollback.

The stage runner must be dedicated to this private repository. Protect `main`
and `develop` in Gitea so unreviewed code cannot reach it.

## What to add next

1. Provision a separate MySQL database whose name includes `test`, and a
   limited database user. Store its connection values as repository Secrets:
   `TORI_TEST_DB_HOST`, `TORI_TEST_DB_PORT`, `TORI_TEST_DB_USER`,
   `TORI_TEST_DB_PASS`, `TORI_TEST_DB_NAME`.
2. Add a manual integration-test workflow that runs
   `php tests/integration/run.php` against that database. It must never use the
   production or stage database.
3. Add a small authenticated health endpoint for the stage site. After that,
   the deployment health check can validate PHP, session startup, and MySQL
   connection instead of checking only the login page.
4. Keep production deployment manual: only after the test stand is approved,
   run a dedicated production workflow from `main`.
