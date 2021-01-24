# IFDB

This is the source code for IFDB, the Interactive Fiction Database.

As of Jan 22, 2021, the code in this repository hasn't been deployed yet. We're going to work together to make this code ready to deploy at a new domain name.

# Preparing Your Own Development Environment

The IFDB web app is a LAMP app (Linux, Apache, MySQL, PHP).

We hope to provide a Docker-based mechanism for launching and running IFDB locally. But for now, if you're not familiar with other LAMP apps, it may be difficult to set up your development environment. We're not providing step-by-step instructions, but general guidelines, intended to be followed by developers who already know how to configure Apache, MySQL, and PHP.

1. You'll need a copy of the IFDB database. Grab the current latest backup from [IFArchive](https://ifarchive.org/indexes/if-archive/info/ifdb/). When you extract the backup, you'll find an `ifdb-archive.sql` script. Install MySQL/MariaDB, create a database named `ifdb`, and run the `ifdb-archive.sql` script in it to restore the backup.

2. The IFArchive backup DB is missing a bunch of tables and columns. Run the `patch-schema.sql` script to bring those back.

3. Create a non-root MySQL user with a password that has access to the DB.

4. Enable MySQL compatibility settings.

    ```
    set global sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
    ```

5. IFDB assumes that it's being hosted at the root of its domain. Set up a [name-based virtual host in Apache](https://httpd.apache.org/docs/2.4/vhosts/name-based.html), allowing `.htaccess` to override all settings. Here's a sample Apache configuration file.

    ```
    <VirtualHost *:80>
    ServerName ifdbdev
    DocumentRoot "/home/YOURUSERNAME/ifdb/www"
    <Directory /home/YOURUSERNAME/ifdb/www>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    </VirtualHost>
    ```

    This example enables a dev site `http://ifdbdev/`. You'll need to add a line `127.0.0.1 ifdbdev` in `/etc/hosts` to use it.

6. All credentials are stored in the `www/local-credentials.php` file, which is ignored by git. There's a template checked in as `local-credentials.php.template`. Create your own local credentials file like this:

    ```
    cp local-credentials.php.template www/local-credentials.php
    ```

    Then, edit the `username` and `password` to be the username and password you set up during your MySQL installation.

7. At this point, you should be able to navigate to `http://ifdbdev/` and see a plausible-looking IFDB home page.

## Known Issues with the Development Environment

* Game box-art images load from the production IFDB site, not the dev environment. IFDB uses a separate "images" database that isn't part of the IFArchive backup. We'll need to generate a backup of that database and make it available on IFArchive, or, at the very least, provide a way for developers to download images from the real IFDB.
* Some searches don't work. IFDB assumes old-style [Henry Spencer regexes](https://dev.mysql.com/doc/refman/8.0/en/regexp.html#regexp-compatibility) that probably won't work on whatever version of MySQL you have installed. A number of search-based features won't work as a result, including the Reviewer Trophy Room on the home page.
* Character encoding issues. Some of these issues appear to be genuine bugs in production IFDB, some of them appear to be issues with the development environment.