<?php
/**
 * Deletes the plugin's options when the user deletes the plugin.
 *
 * @package BulkPasswordProtectPostTypes
 */

// If uninstall.php is not called by WordPress, stop.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'pass_protect_all_select_types' );
delete_option( 'pass_protect_all_pass_field' );
