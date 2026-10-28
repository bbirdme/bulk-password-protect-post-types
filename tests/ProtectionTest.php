<?php
/**
 * Tests of what a visitor gets: which posts are protected and where the text stays hidden.
 *
 * @package BulkPasswordProtectPostTypes
 */

namespace BulkPasswordProtectPostTypes\Tests;

use WP_Query;
use WP_REST_Request;

/**
 * The plugin's one job: every post of the chosen post types asks for the password.
 */
class ProtectionTest extends TestCase {

	/**
	 * A post and a page, each with text a visitor could read.
	 *
	 * @return int[] The post's and the page's ID.
	 */
	private function content() {
		return array(
			self::factory()->post->create(
				array(
					'post_title'   => 'Secret post',
					'post_content' => 'TOPSECRET-BODY',
				)
			),
			self::factory()->post->create(
				array(
					'post_type'    => 'page',
					'post_title'   => 'Open page',
					'post_content' => 'OPEN-BODY',
				)
			),
		);
	}

	/**
	 * A post of a chosen type asks for the password and hides its text.
	 */
	public function test_chosen_post_type_asks_for_the_password() {
		list( $post_id ) = $this->content();
		$this->protect( array( 'post' ) );

		$this->open( $post_id );

		$this->assertTrue( post_password_required() );
		$this->assertStringNotContainsString( 'TOPSECRET-BODY', get_the_content() );
		$this->assertStringContainsString( 'name="post_password"', get_the_content(), 'The visitor gets the password form.' );
	}

	/**
	 * A type that was not chosen is left alone.
	 */
	public function test_other_post_types_stay_open() {
		list( , $page_id ) = $this->content();
		$this->protect( array( 'post' ) );

		$this->open( $page_id );

		$this->assertFalse( post_password_required() );
		$this->assertStringContainsString( 'OPEN-BODY', get_the_content() );
	}

	/**
	 * Every chosen type is protected, not only the first.
	 */
	public function test_several_chosen_types_are_all_protected() {
		list( $post_id, $page_id ) = $this->content();
		$this->protect( array( 'post', 'page' ) );

		$this->open( $post_id );
		$this->assertTrue( post_password_required() );

		$this->open( $page_id );
		$this->assertTrue( post_password_required() );
	}

	/**
	 * The password the visitor types opens the post; a different one does not.
	 */
	public function test_the_chosen_password_opens_the_post() {
		list( $post_id ) = $this->content();
		$this->protect( array( 'post' ) );

		$this->visitor_typed( 'not-the-password' );
		$this->open( $post_id );
		$this->assertTrue( post_password_required(), 'A wrong password does not open it.' );

		$this->visitor_typed( self::PASSWORD );
		$this->open( $post_id );
		$this->assertFalse( post_password_required() );
		$this->assertStringContainsString( 'TOPSECRET-BODY', get_the_content() );
	}

	/**
	 * Without a password nothing can be protected, and without a choice nothing is.
	 */
	public function test_nothing_is_protected_without_a_password_or_a_choice() {
		list( $post_id ) = $this->content();

		$this->open( $post_id );
		$this->assertFalse( post_password_required(), 'Nothing is configured.' );

		$this->protect( array( 'post' ), '' );
		$this->open( $post_id );
		$this->assertFalse( post_password_required(), 'A type is chosen but there is no password.' );

		$this->protect( array(), self::PASSWORD );
		$this->open( $post_id );
		$this->assertFalse( post_password_required(), 'A password is set but no type is chosen.' );
	}

	/**
	 * The password of the setting replaces one a post had of its own.
	 */
	public function test_the_setting_replaces_a_password_a_post_had() {
		list( $post_id ) = $this->content();
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_password' => 'its-own',
			)
		);
		$this->protect( array( 'post' ) );

		$post = $this->open( $post_id );

		$this->assertSame( self::PASSWORD, $post->post_password );
	}

	/**
	 * Search results go through the same loop, so they are protected too.
	 */
	public function test_search_results_are_protected() {
		$this->content();
		$this->protect( array( 'post' ) );

		$query = new WP_Query( array( 's' => 'Secret' ) );
		$this->assertTrue( $query->have_posts() );
		$query->the_post();

		$this->assertTrue( post_password_required() );
		$this->assertStringNotContainsString( 'TOPSECRET-BODY', get_the_content() );
		wp_reset_postdata();
	}

	/**
	 * The REST API does not hand out the text of a protected post, in a list or on its own.
	 */
	public function test_rest_api_hides_the_text() {
		list( $post_id, $page_id ) = $this->content();
		$this->protect( array( 'post' ) );

		$single = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id ) )->get_data();
		$this->assertTrue( $single['content']['protected'] );
		$this->assertStringNotContainsString( 'TOPSECRET-BODY', $single['content']['rendered'] );

		$list = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/posts' ) )->get_data();
		$this->assertStringNotContainsString( 'TOPSECRET-BODY', wp_json_encode( $list ) );

		// The control: what was not chosen is still readable, so the checks above can fail.
		$page = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/pages/' . $page_id ) )->get_data();
		$this->assertStringContainsString( 'OPEN-BODY', $page['content']['rendered'] );
	}

	/**
	 * A post type with a password of its own asks for that one, and the others keep the general one.
	 */
	public function test_a_post_type_can_have_its_own_password() {
		list( $post_id, $page_id ) = $this->content();
		$this->protect( array( 'post', 'page' ) );
		$this->own_passwords( array( 'page' => 'pages-only' ) );

		$this->assertSame( self::PASSWORD, $this->open( $post_id )->post_password, 'A post uses the general password.' );
		$this->assertSame( 'pages-only', $this->open( $page_id )->post_password, 'A page uses its own.' );

		$this->visitor_typed( self::PASSWORD );
		$this->open( $page_id );
		$this->assertTrue( post_password_required(), 'The general password does not open a page.' );

		$this->visitor_typed( 'pages-only' );
		$this->open( $page_id );
		$this->assertFalse( post_password_required() );
	}

	/**
	 * A type with a password of its own is protected even when there is no general password.
	 */
	public function test_own_password_needs_no_general_password() {
		list( $post_id, $page_id ) = $this->content();
		$this->protect( array( 'post', 'page' ), '' );
		$this->own_passwords( array( 'page' => 'pages-only' ) );

		$this->open( $post_id );
		$this->assertFalse( post_password_required(), 'A post has no password at all.' );

		$this->open( $page_id );
		$this->assertTrue( post_password_required() );
	}

	/**
	 * A password of its own does nothing for a type that was not chosen.
	 */
	public function test_own_password_of_an_unchosen_type_does_nothing() {
		list( , $page_id ) = $this->content();
		$this->protect( array( 'post' ) );
		$this->own_passwords( array( 'page' => 'pages-only' ) );

		$this->open( $page_id );

		$this->assertFalse( post_password_required() );
	}

	/**
	 * The filter lets a site leave some posts open, such as every post of one category.
	 */
	public function test_filter_can_leave_a_category_open() {
		$secret = self::factory()->post->create( array( 'post_content' => 'TOPSECRET-BODY' ) );
		$open   = self::factory()->post->create( array( 'post_content' => 'OPEN-BODY' ) );
		wp_set_post_categories( $open, array( self::factory()->category->create( array( 'slug' => 'public-news' ) ) ) );
		$this->protect( array( 'post' ) );

		add_filter(
			'pass_protect_all_post_password',
			static function ( $password, $post ) {
				return has_category( 'public-news', $post ) ? '' : $password;
			},
			10,
			2
		);

		$this->open( $secret );
		$this->assertTrue( post_password_required() );

		$this->open( $open );
		$this->assertFalse( post_password_required(), 'The post in the category stays open.' );
		$this->assertStringContainsString( 'OPEN-BODY', get_the_content() );
	}

	/**
	 * The filter gets the password the settings give the post, and the post.
	 */
	public function test_filter_gets_the_password_of_the_settings_and_the_post() {
		list( $post_id ) = $this->content();
		$this->protect( array( 'post' ) );
		$seen = array();
		add_filter(
			'pass_protect_all_post_password',
			static function ( $password, $post ) use ( &$seen ) {
				$seen[] = array( $password, $post->ID );
				return $password;
			},
			10,
			2
		);

		$this->open( $post_id );

		$this->assertContains( array( self::PASSWORD, $post_id ), $seen );
	}

	/**
	 * The filter can protect a post of a type that was not chosen, or give a post another password.
	 */
	public function test_filter_can_protect_other_posts_and_change_the_password() {
		list( $post_id, $page_id ) = $this->content();
		$this->protect( array( 'post' ) );
		add_filter(
			'pass_protect_all_post_password',
			static function ( $password, $post ) use ( $page_id ) {
				return $post->ID === $page_id ? 'for-this-page' : strrev( $password );
			},
			10,
			2
		);

		$this->assertSame( strrev( self::PASSWORD ), $this->open( $post_id )->post_password );
		$this->assertSame( 'for-this-page', $this->open( $page_id )->post_password, 'A page, although pages were not chosen.' );
	}

	/**
	 * A filter that returns something that is not a password, such as false, protects nothing and
	 * breaks nothing.
	 */
	public function test_filter_result_that_is_not_a_password_protects_nothing() {
		list( $post_id ) = $this->content();
		$this->protect( array( 'post' ) );
		add_filter( 'pass_protect_all_post_password', '__return_false' );

		$this->open( $post_id );

		$this->assertFalse( post_password_required() );
	}
}
