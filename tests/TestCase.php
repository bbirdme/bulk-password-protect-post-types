<?php
/**
 * What every test of the plugin shares.
 *
 * @package BulkPasswordProtectPostTypes
 */

namespace BulkPasswordProtectPostTypes\Tests;

use WP_UnitTestCase;

/**
 * Base class: registers the plugin's settings the way the admin does and gives each test a clean
 * set of options and cookies.
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * The password the tests protect posts with.
	 */
	const PASSWORD = 'letmein';

	/**
	 * Makes the settings API's admin functions available and registers the plugin's settings.
	 */
	public function set_up() {
		parent::set_up();
		if ( ! function_exists( 'settings_fields' ) ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}
		pass_protect_all_settings_init();
	}

	/**
	 * Removes the visitor's password cookie, which lives in a superglobal the rollback does not touch.
	 */
	public function tear_down() {
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		parent::tear_down();
	}

	/**
	 * Chooses the post types to protect and the password, as the settings screen saves them.
	 *
	 * @param string[] $types    Post type names.
	 * @param string   $password The password.
	 */
	protected function protect( array $types, $password = self::PASSWORD ) {
		update_option( 'pass_protect_all_select_types', $types );
		update_option( 'pass_protect_all_pass_field', $password );
	}

	/**
	 * Gives post types a password of their own, as the settings screen saves them.
	 *
	 * @param array<string, string> $passwords Password by post type name.
	 */
	protected function own_passwords( array $passwords ) {
		update_option( 'pass_protect_all_type_passwords', $passwords );
	}

	/**
	 * Makes the visitor's browser send the cookie WordPress sets after a correct password.
	 *
	 * @param string $password What the visitor typed.
	 */
	protected function visitor_typed( $password ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher                                 = new \PasswordHash( 8, true );
		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = $hasher->HashPassword( $password );
	}

	/**
	 * Opens a post as a visitor's page does, and moves the loop to it.
	 *
	 * @param int $post_id Post ID.
	 * @return \WP_Post The post as the loop sees it.
	 */
	protected function open( $post_id ) {
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( have_posts(), 'The front end finds the post.' );
		the_post();
		return get_post();
	}
}
