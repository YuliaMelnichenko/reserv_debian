# Gitea CI/CD

## What is already in the repository

`quality.yml` runs for pushes, pull requests, and manual starts on `main` and
`develop`. It checks every PHP file with `php -l`, runs `php tests/run.php`,
and rejects whitespace errors. The same suite runs with PHP 8.2 and PHP 8.5.

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

3. Create a small dedicated Debian virtual machine for the runner. Do not run
   arbitrary CI jobs on the Gitea server or the production web server. Install
   Docker, Git, and the current Gitea `act_runner` there.

4. In the project directory on the runner VM, build the two local CI images:

   ```bash
   docker build -t tori/php-ci:8.2 -f ci/php82.Dockerfile .
   docker build -t tori/php-ci:8.5 -f ci/php85.Dockerfile .
   ```

5. Register a repository-level runner with these labels:

   ```text
   php-8.2:docker://tori/php-ci:8.2,
   php-8.5:docker://tori/php-ci:8.5
   ```

   Obtain the runner registration token from `Repository settings -> Actions ->
   Runners`. Keep it only on the runner VM; do not add it to this repository or
   send it in chat.

6. After the runner is online, push this configuration to Gitea. The first
   push will automatically start two checks in the `Actions` tab.

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
