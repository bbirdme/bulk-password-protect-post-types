# Bulk Password Protect Post Types

WordPress plugin: choose post types, set one password, and every post of those types asks for it.
Published at https://wordpress.org/plugins/bulk-password-protect-post-types/ (SVN is the release channel; this repository is the source).

## Tests

The tests use WordPress's own PHPUnit suite and need a MySQL/MariaDB database that they **empty on every run**.

```
composer install
docker run -d --name swiftling-tests-db -p 127.0.0.1:33061:3306 -e MYSQL_ROOT_PASSWORD=root mariadb:11.8   # once
docker exec swiftling-tests-db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS bpp_tests"            # once
vendor/bin/phpunit
vendor/bin/phpcs
```

The connection comes from `WP_TESTS_DB_NAME`, `WP_TESTS_DB_USER`, `WP_TESTS_DB_PASSWORD` and `WP_TESTS_DB_HOST`; the defaults match the commands above.

To check an older WordPress (5.9 is the lowest this suite can run on), copy the repository without `vendor/`, set `johnpbloch/wordpress-core` and `wp-phpunit/wp-phpunit` in the copy's `composer.json` to e.g. `5.9.*`, delete `composer.lock`, run `composer update`, then `vendor/bin/phpunit`. Use PHP 7.4 for the oldest cores.

## Releasing

1. Raise `Version` in `bulk-password.php` and `Stable tag` in `readme.txt`, and add the changelog entry (`PackageTest` fails if they disagree).
2. Copy the plugin into SVN `trunk/` following `.distignore`, the images of `.wordpress-org/` into `assets/`, then `svn cp trunk tags/<version>` and commit.
