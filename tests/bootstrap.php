<?php
/**
 * Starts WordPress's test suite with the plugin loaded.
 *
 * The suite installs WordPress into the database named in tests/wp-tests-config.php, loads the
 * plugin as a must-use plugin would, and runs each test in a transaction it rolls back.
 *
 * @package BulkPasswordProtectPostTypes
 */

$bulk_password_root = dirname( __DIR__ );

// The PHPUnit Polyfills, which WordPress's suite needs.
require_once $bulk_password_root . '/vendor/autoload.php';

if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Where wp-phpunit looks for the configuration.
}

$bulk_password_wp_tests = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $bulk_password_root . '/vendor/wp-phpunit/wp-phpunit';

require_once $bulk_password_wp_tests . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $bulk_password_root ) {
		require $bulk_password_root . '/bulk-password.php';
	}
);

require $bulk_password_wp_tests . '/includes/bootstrap.php';
require_once __DIR__ . '/TestCase.php';
