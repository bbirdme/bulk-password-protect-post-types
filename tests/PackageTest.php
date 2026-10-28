<?php
/**
 * Tests of what ships: the plugin's header, readme, translations and uninstall.
 *
 * @package BulkPasswordProtectPostTypes
 */

namespace BulkPasswordProtectPostTypes\Tests;

/**
 * Things that go wrong between releases and that nobody notices until wordpress.org does.
 */
class PackageTest extends TestCase {

	/**
	 * The directory name wordpress.org gives the plugin, which is also its text domain.
	 */
	const SLUG = 'bulk-password-protect-post-types';

	/**
	 * The plugin's header fields.
	 *
	 * @return array<string, string>
	 */
	private function header() {
		return get_file_data(
			dirname( __DIR__ ) . '/bulk-password.php',
			array(
				'version'     => 'Version',
				'text_domain' => 'Text Domain',
				'plugin_uri'  => 'Plugin URI',
				'php'         => 'Requires PHP',
				'wp'          => 'Requires at least',
			)
		);
	}

	/**
	 * Every translatable string uses the text domain of the header, and that is the slug.
	 */
	public function test_one_text_domain_everywhere() {
		$header = $this->header();
		$this->assertSame( self::SLUG, $header['text_domain'] );

		$code = file_get_contents( dirname( __DIR__ ) . '/bulk-password.php' );
		preg_match_all( '/\b(?:__|_e|_x|_n|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(.*?,\s*[\'"]([^\'"]+)[\'"]\s*\)/s', $code, $found );
		$this->assertNotEmpty( $found[1], 'The scan finds the strings.' );
		$this->assertSame( array( self::SLUG ), array_values( array_unique( $found[1] ) ) );
		$this->assertStringNotContainsString( 'load_plugin_textdomain', $code, 'wordpress.org loads the translations of a hosted plugin itself.' );
	}

	/**
	 * The version in the header is the stable tag in the readme, so a release cannot ship the old one.
	 */
	public function test_readme_stable_tag_is_the_plugin_version() {
		$readme = file_get_contents( dirname( __DIR__ ) . '/readme.txt' );
		preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $tag );

		$this->assertSame( $this->header()['version'], $tag[1] );
		$this->assertMatchesRegularExpression( '/^= ' . preg_quote( $tag[1], '/' ) . ' =$/m', $readme, 'The changelog has this version.' );
	}

	/**
	 * The header names the plugin's own page, the minimum versions, and nothing points to another slug.
	 */
	public function test_header_is_complete() {
		$header = $this->header();

		$this->assertSame( 'https://wordpress.org/plugins/' . self::SLUG . '/', $header['plugin_uri'] );
		$this->assertNotSame( '', $header['php'] );
		$this->assertNotSame( '', $header['wp'] );
	}

	/**
	 * Deleting the plugin removes its options and nothing else.
	 */
	public function test_uninstall_removes_the_options() {
		$this->protect( array( 'post' ) );
		$this->own_passwords( array( 'page' => 'pages-only' ) );
		update_option( 'some_other_option', 'kept' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', self::SLUG . '/bulk-password.php' );
		}
		require dirname( __DIR__ ) . '/uninstall.php';

		$this->assertFalse( get_option( 'pass_protect_all_select_types' ) );
		$this->assertFalse( get_option( 'pass_protect_all_pass_field' ) );
		$this->assertFalse( get_option( 'pass_protect_all_type_passwords' ) );
		$this->assertSame( 'kept', get_option( 'some_other_option' ) );
	}

	/**
	 * On a network, deleting the plugin cleans every site, not only the one it runs on.
	 */
	public function test_uninstall_cleans_every_site_of_a_network() {
		$this->skipWithoutMultisite();
		$other = self::factory()->blog->create();

		$this->protect( array( 'post' ) );
		switch_to_blog( $other );
		$this->protect( array( 'page' ) );
		restore_current_blog();

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', self::SLUG . '/bulk-password.php' );
		}
		require dirname( __DIR__ ) . '/uninstall.php';

		$this->assertFalse( get_option( 'pass_protect_all_select_types' ) );
		switch_to_blog( $other );
		$this->assertFalse( get_option( 'pass_protect_all_select_types' ), 'The other site is clean too.' );
		restore_current_blog();
	}
}
