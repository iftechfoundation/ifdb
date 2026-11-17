# IFDB

This is the source code for [IFDB](https://ifdb.org), the Interactive Fiction Database.

# Preparing Your Own Development Environment

The IFDB web app is a LAMP app (Linux, Apache, MySQL, PHP). The development environment runs on Docker.

1. Install Docker and Docker Compose. [https://docs.docker.com/get-docker/](https://docs.docker.com/get-docker/)

2. Run `./prepare_dev_environment.sh` in this directory. That will download the current latest IFDB database backup from IFArchive, and put it in a place where Docker will expect to find it.

3. Run `docker compose up --build`. That will launch a MySQL Docker container and an Apache container with PHP, available on port 8080.

4. Go to `http://localhost:8080` on your machine. You should see IFDB running. You can login as `ifdbadmin` at `ifdb.org` with the password `secret` to sign in as an administrator. The administrator has access to the "system maintenance panel" at `http://localhost:8080/adminops`.

5. Alternatively, you can login as `test` at `ifdb.org` with the password `secret` to sign in as a regular test user named `Test Tester`.

6. Optionally, you can query the database using phpMyAdmin at `http://localhost:8081` or run `./query-docker.sh` to use the MySQL command-line interface.

# Database Changes

Changes to the database's schema or content are made with migrations: SQL files in `./sql/migrations`, applied by [dbmate](https://github.com/amacneil/dbmate). Each database keeps track of which migrations it has already applied. Your local database applies any new ones whenever you start it with `docker compose up`, and dev.ifdb.org and ifdb.org apply them when they're deployed.

Once your development environment is set up, you can make a database change at any time, whether or not it's running:

1. Create a migration file:

   ```
   ./docker-dbmate.sh new add_example_to_games
   ```

   Replace `add_example_to_games` with a few words describing your change, in lowercase with underscores instead of spaces. The command creates `sql/migrations/<timestamp>_add_example_to_games.sql`.

2. In that file, put the SQL for your change under `-- migrate:up`, and SQL that undoes it under `-- migrate:down`:

   ```
   -- migrate:up
   ALTER TABLE games ADD COLUMN example VARCHAR(255);

   -- migrate:down
   ALTER TABLE games DROP COLUMN example;
   ```

   If your change can't be undone, put `SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Irreversible';` under `-- migrate:down`, so that trying to undo it fails with an error.

3. Apply it to your local database:

   ```
   ./docker-dbmate.sh
   ```

   That applies every migration your database doesn't have yet, so you never name a specific one. You don't need to restart anything.

To change a migration you've applied but haven't merged yet, edit the file, then rebuild your local database with `docker compose down` and `docker compose up`. Once a migration is merged, don't edit it; databases that already applied it won't run it again. Make a new migration instead.

# Updating PHP dependencies

Our PHP dependencies are checked into `composer.json` and `composer.lock`. To update those files, you'll need to run `composer` inside the Docker container, using the `docker-php-composer.sh` script, like this:

```
docker-php-composer.sh update
docker-php-composer.sh require whatever/dependency
```

Then, stop your server and restart your dev environment with these commands:

```
./prepare_dev_environment.sh
docker compose down
docker compose up --build
```

Once your dependency is installed, you'll consume the dependency by adding a `require_once 'vendor/autoload.php';` line to your code.

# PHP coding style

We use [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) for coding style.

Run it with Docker:

```
./docker-php-cs-fixer.sh
./docker-php-cs-fixer.sh --dry-run --diff
```

## Known Issues with the Development Environment

* Sending email doesn't work. That's unfortunate, because if you want to create a user, you'll need to login with an activation code. After you try to create a new user, you should be able to see the email text in the Docker logs. Search for `EMAIL: NOT SENDING EMAIL IN LOCAL DEVELOPMENT MODE`.
* Existing game box-art images load from the production IFDB site. IFDB uses a separate "images" database that isn't part of the IFArchive backup. You can upload your own cover art in the local environment; newly uploaded images are stored and served locally.

## Troubleshooting

To reset the Docker environment, run this:

```
./prepare_dev_environment.sh && docker compose down && docker compose up --build
```

`./prepare_dev_environment.sh` populates the `initdb` directory, which is only run when you `docker compose up` on a container for the _first_ time.

If you just `docker compose up`, it will launch the container you already built. If you `docker compose down` and `docker compose up` without `--build`, it will reuse the existing Docker image, without incorporating changes to the `Dockerfile`.

If you see a 500 error loading `http://localhost:8080`, check the Docker logs. If you see one of these errors, then something went wrong during the `./prepare_dev_environment.sh` step.

* `PHP Warning:  include_once(local-credentials.php): failed to open stream: No such file or directory`
* `PHP Warning:  include_once(): Failed opening 'local-credentials.php' for inclusion`
* `PHP Fatal error:  Uncaught Error: Call to undefined function localCredentials()`

If you see these errors, run `./prepare_dev_environment.sh && docker compose down && docker compose up --build` again.
