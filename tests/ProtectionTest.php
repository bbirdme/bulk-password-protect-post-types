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
}
