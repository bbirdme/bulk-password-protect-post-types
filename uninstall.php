<?php
// Deletes options from database, file is run automatically when the users deletes the plugin
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('pass_protect_all_select_types');
delete_option('pass_protect_all_pass_field');
