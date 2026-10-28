<?php
/**
 * Tests of the settings screen and of what it saves.
 *
 * @package BulkPasswordProtectPostTypes
 */

namespace BulkPasswordProtectPostTypes\Tests;

/**
 * The screen under Settings: it renders, escapes what it prints, and saves only what makes sense.
 */
class SettingsTest extends TestCase {

	/**
	 * The post type checkboxes, as the settings screen prints them.
	 *
	 * @return string HTML.
	 */
	private function types_field() {
		ob_start();
		pass_protect_all_select_types_render( array( 'pass_protect_all_select_types' ) );
		return ob_get_clean();
	}

	/**
	 * The password field, as the settings screen prints it.
	 *
	 * @return string HTML.
	 */
	private function password_field() {
		ob_start();
		pass_protect_all_pass_field_render( array( 'pass_protect_all_pass_field' ) );
		return ob_get_clean();
	}

	/**
	 * The screen renders on PHP 8, with a checkbox for each post type, named as the admin menu names it.
	 */
	public function test_post_type_field_renders_a_checkbox_per_type() {
		$html = $this->types_field();

		$this->assertStringContainsString( 'name="pass_protect_all_select_types[]"', $html );
		$this->assertMatchesRegularExpression( '/value="post"/', $html );
		$this->assertMatchesRegularExpression( '/value="page"/', $html );
		$this->assertStringContainsString( 'Posts (post)', $html );
		$this->assertStringContainsString( 'Pages (page)', $html );
		$this->assertStringNotContainsString( 'checked', $html, 'Nothing is chosen yet.' );
	}

	/**
	 * Only post types visitors can see are offered; WordPress's internal ones are not.
	 */
	public function test_internal_post_types_are_not_offered() {
		register_post_type(
			'book',
			array(
				'public' => true,
				'labels' => array( 'name' => 'Books' ),
			)
		);
		register_post_type( 'audit_log', array( 'public' => false ) );

		$html = $this->types_field();

		$this->assertStringContainsString( 'Books (book)', $html, 'A custom public type is offered.' );
		foreach ( array( 'revision', 'nav_menu_item', 'wp_block', 'wp_template', 'audit_log' ) as $internal ) {
			$this->assertStringNotContainsString( 'value="' . $internal . '"', $html );
		}

		unregister_post_type( 'book' );
		unregister_post_type( 'audit_log' );
	}

	/**
	 * A type chosen with version 1.0, which listed everything, stays on the screen and checked.
	 */
	public function test_a_chosen_internal_type_stays_listed() {
		$this->protect( array( 'wp_block' ) );

		$this->assertMatchesRegularExpression( '/value="wp_block"\s+checked="checked"/', $this->types_field() );
	}

	/**
	 * What was saved shows as checked, and only that.
	 */
	public function test_saved_types_are_checked() {
		$this->protect( array( 'page' ) );

		$html = $this->types_field();

		$this->assertMatchesRegularExpression( '/value="page"\s+checked="checked"/', $html );
		$this->assertDoesNotMatchRegularExpression( '/value="post"\s+checked/', $html );
	}

	/**
	 * The saved password is printed escaped, so a quote cannot break out of the field.
	 */
	public function test_password_field_escapes_the_password() {
		$this->protect( array( 'post' ), '"><script>alert(1)</script>' );

		$html = $this->password_field();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&quot;&gt;&lt;script&gt;', $html );
	}

	/**
	 * The page under Settings is a form that posts to options.php with the plugin's option group.
	 */
	public function test_options_page_is_a_settings_form() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		ob_start();
		pass_protect_all_options_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( "action='options.php'", $html );
		$this->assertStringContainsString( "name='option_page' value='pass_protect_all_settings_page'", $html, 'The option group.' );
		$this->assertStringContainsString( 'name="pass_protect_all_pass_field"', $html );
		$this->assertStringContainsString( 'name="pass_protect_all_select_types[]"', $html );
	}

	/**
	 * Only existing post types are saved, whatever the request sends.
	 */
	public function test_saving_keeps_only_real_post_types() {
		update_option( 'pass_protect_all_select_types', array( 'post', 'no-such-type', '<b>x</b>', 'page' ) );
		$this->assertSame( array( 'post', 'page' ), get_option( 'pass_protect_all_select_types' ) );

		update_option( 'pass_protect_all_select_types', 'post' );
		$this->assertSame( array(), get_option( 'pass_protect_all_select_types' ), 'A single string is not a list.' );
	}

	/**
	 * The password is saved as text and trimmed, within what the database column holds.
	 */
	public function test_saving_cleans_the_password() {
		update_option( 'pass_protect_all_pass_field', "  secret \n" );
		$this->assertSame( 'secret', get_option( 'pass_protect_all_pass_field' ) );

		update_option( 'pass_protect_all_pass_field', array( 'secret' ) );
		$this->assertSame( '', get_option( 'pass_protect_all_pass_field' ), 'An array is not a password.' );

		update_option( 'pass_protect_all_pass_field', str_repeat( 'a', 300 ) );
		$this->assertSame( 255, strlen( get_option( 'pass_protect_all_pass_field' ) ), 'wp_posts.post_password holds 255.' );

		update_option( 'pass_protect_all_pass_field', 'p<a>ss&"word' );
		$this->assertSame( 'p<a>ss&"word', get_option( 'pass_protect_all_pass_field' ), 'Characters are kept: the visitor has to type exactly this.' );
	}

	/**
	 * The Settings link on the Plugins screen follows the site's admin address.
	 */
	public function test_settings_link_follows_the_admin_address() {
		add_filter(
			'admin_url',
			static function ( $url, $path ) {
				return 'https://example.org/custom-admin/' . $path;
			},
			10,
			2
		);

		$links = apply_filters( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/bulk-password.php' ), array( 'deactivate' => 'x' ) );

		$this->assertArrayHasKey( 'settings', $links );
		$this->assertStringContainsString( 'href="https://example.org/custom-admin/options-general.php?page=password_protect_all_posts"', $links['settings'] );
		$this->assertArrayHasKey( 'deactivate', $links, 'The other links stay.' );
	}
}
