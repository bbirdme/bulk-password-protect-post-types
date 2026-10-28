<?php
/**
 * Plugin Name: Bulk Password Protect Post Types
 * Plugin URI: https://wordpress.org/plugins/bulk-password-protect-post-types/
 * Description: Bulk Password Protect Post Types enables you to easily password protect all posts from selected post type.
 * Author: Bruno Kos
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author URI: http://bbird.me/
 * Text Domain: bulk-password-protect-post-types
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package BulkPasswordProtectPostTypes
 */

// Making sure to direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register menu option for the plugin.
add_action( 'admin_menu', 'pass_protect_all_add_admin_menu' ); // Add extra submenus and menu options to the admin panel.
add_action( 'admin_init', 'pass_protect_all_settings_init' );

// Add a link to the settings page.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pass_protect_all_settings_link' );

/**
 * Adds a Settings link to the plugin's row on the Plugins screen.
 *
 * @param string[] $links The links WordPress shows for the plugin.
 * @return string[] The links, with Settings first.
 */
function pass_protect_all_settings_link( $links ) {
	$url = admin_url( 'options-general.php?page=password_protect_all_posts' );

	return array_merge(
		array(
			'settings' => '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'bulk-password-protect-post-types' ) . '</a>',
		),
		$links
	);
}

/**
 * Adds the settings page under Settings.
 */
function pass_protect_all_add_admin_menu() {
	add_options_page(
		__( 'Bulk Password Protect Settings', 'bulk-password-protect-post-types' ), // The title of the page.
		__( 'Bulk Password Protect', 'bulk-password-protect-post-types' ),          // The text of the menu item.
		'manage_options',                                                           // The capability required to see it.
		'password_protect_all_posts',                                               // The slug of the page.
		'pass_protect_all_options_page'                                             // Prints the page.
	);
}

/**
 * Registers the plugin's settings, their section and their fields.
 */
function pass_protect_all_settings_init() {
	add_settings_section(
		'pass_protect_all_settings_section',
		__( 'Bulk Password Protect Post Types Settings', 'bulk-password-protect-post-types' ),
		'pass_protect_all_settings_section_callback',
		'pass_protect_all_settings_page'
	);

	add_settings_field(
		'pass_protect_all_select_types',
		__( 'Post Type', 'bulk-password-protect-post-types' ),
		'pass_protect_all_select_types_render',
		'pass_protect_all_settings_page',
		'pass_protect_all_settings_section',
		array( 'pass_protect_all_select_types' ) // The option's name, passed to the render function.
	);

	add_settings_field(
		'pass_protect_all_pass_field',
		__( 'Choose password for posts', 'bulk-password-protect-post-types' ),
		'pass_protect_all_pass_field_render',
		'pass_protect_all_settings_page',
		'pass_protect_all_settings_section',
		array( 'pass_protect_all_pass_field' )
	);

	register_setting(
		'pass_protect_all_settings_page',
		'pass_protect_all_select_types',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pass_protect_all_sanitize_types',
		)
	);
	register_setting(
		'pass_protect_all_settings_page',
		'pass_protect_all_pass_field',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'pass_protect_all_sanitize_password',
		)
	);
}

/**
 * Keeps only the names of post types that exist.
 *
 * @param mixed $value What the settings form sent.
 * @return string[] Post type names, in the order they were sent.
 */
function pass_protect_all_sanitize_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	return array_values( array_intersect( array_filter( $value, 'is_string' ), get_post_types() ) );
}

/**
 * Trims the password to what the posts table can hold. Its characters are kept as typed, because
 * the visitor has to type exactly the same.
 *
 * @param mixed $value What the settings form sent.
 * @return string The password.
 */
function pass_protect_all_sanitize_password( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}

	return mb_substr( trim( $value ), 0, 255 ); // wp_posts.post_password is a varchar(255).
}

/**
 * The post types the settings screen offers: the ones visitors can see, and any that are already
 * chosen, so a choice never disappears from the screen.
 *
 * @return WP_Post_Type[] Post types, by name.
 */
function pass_protect_all_choosable_post_types() {
	$chosen = get_option( 'pass_protect_all_select_types' );
	if ( ! is_array( $chosen ) ) {
		$chosen = array();
	}

	$choosable = array();
	foreach ( get_post_types( array(), 'objects' ) as $name => $post_type ) {
		if ( $post_type->public || in_array( $name, $chosen, true ) ) {
			$choosable[ $name ] = $post_type;
		}
	}

	return $choosable;
}

/**
 * Prints a checkbox for every post type visitors can see, with its name and its internal name.
 *
 * @param string[] $args The option's name, as add_settings_field passed it.
 */
function pass_protect_all_select_types_render( $args ) {
	$name     = $args[0];
	$selected = get_option( $name );
	if ( ! is_array( $selected ) ) {
		$selected = array();
	}

	echo '<p>' . esc_html__( 'Choose one or more post types. All posts within selected will be password-protected.', 'bulk-password-protect-post-types' ) . '</p><br>';

	foreach ( pass_protect_all_choosable_post_types() as $post_type => $object ) {
		printf(
			'<input type="checkbox" id="%1$s[%2$s]" name="%1$s[]" value="%2$s"%3$s /><label for="%1$s[%2$s]"> %4$s (%2$s)</label><br>',
			esc_attr( $name ),
			esc_attr( $post_type ),
			in_array( $post_type, $selected, true ) ? ' checked="checked"' : '',
			esc_html( $object->labels->name )
		);
	}
}

/**
 * Prints the password field.
 *
 * @param string[] $args The option's name, as add_settings_field passed it.
 */
function pass_protect_all_pass_field_render( $args ) {
	$name     = $args[0];
	$password = get_option( $name );
	if ( ! is_string( $password ) ) {
		$password = '';
	}

	echo '<p>' . esc_html__( 'Make sure to set password, otherwise posts will not be (for obvious reasons) protected.', 'bulk-password-protect-post-types' ) . '</p><br>';

	printf(
		'<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" autocomplete="off" />',
		esc_attr( $name ),
		esc_attr( $password ),
		esc_attr__( 'Password', 'bulk-password-protect-post-types' )
	);
}

/**
 * Gives a post the password of the setting when it belongs to one of the chosen post types.
 *
 * It runs for every post a loop, a feed, a search or the REST API sets up, so the text stays hidden
 * wherever WordPress would print it. Without a password nothing is changed, so a post's own
 * password is not removed.
 *
 * @param WP_Post $post_object The post, passed by the `the_post` action.
 */
function pass_protect_all_set_password( $post_object ) {
	$chosen_post_types = get_option( 'pass_protect_all_select_types' );
	$post_password     = get_option( 'pass_protect_all_pass_field' );

	if ( ! is_array( $chosen_post_types ) || ! is_string( $post_password ) || '' === $post_password ) {
		return;
	}

	if ( in_array( $post_object->post_type, $chosen_post_types, true ) ) {
		$post_object->post_password = $post_password;
	}
}
add_action( 'the_post', 'pass_protect_all_set_password' );

/**
 * Prints nothing above the fields: the section has no text of its own.
 */
function pass_protect_all_settings_section_callback() {
	// Nothing to explain here.
}

/**
 * Prints the settings page.
 */
function pass_protect_all_options_page() {
	?>
	<div class="wrap">
		<form action='options.php' method='post'>
			<?php
			settings_fields( 'pass_protect_all_settings_page' );       // Output nonce, action, and option_page fields for a settings page.
			do_settings_sections( 'pass_protect_all_settings_page' );  // Prints out all settings sections added to a particular settings page.
			submit_button();
			?>
		</form>
	</div>
	<?php
}
