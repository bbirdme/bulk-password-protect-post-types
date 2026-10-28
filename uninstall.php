<?php
/**
 * Deletes the plugin's options when the user deletes the plugin, on every site of a network.
 *
 * @package BulkPasswordProtectPostTypes
 */

// If uninstall.php is not called by WordPress, stop.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$pass_protect_all_options = array(
	'pass_protect_all_select_types',
	'pass_protect_all_type_passwords',
	'pass_protect_all_pass_field',
);

// The options belong to each site, so on a network every site is cleaned.
$pass_protect_all_sites = is_multisite() ? get_sites(
	array(
		'fields' => 'ids',
		'number' => 0, // No limit.
	)
) : array( get_current_blog_id() );

foreach ( $pass_protect_all_sites as $pass_protect_all_site ) {
	if ( is_multisite() ) {
		switch_to_blog( $pass_protect_all_site );
	}

	foreach ( $pass_protect_all_options as $pass_protect_all_option ) {
		delete_option( $pass_protect_all_option );
	}

	if ( is_multisite() ) {
		restore_current_blog();
	}
}
