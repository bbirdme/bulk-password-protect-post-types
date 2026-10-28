<?php
/*
Plugin Name: Bulk Password Protect Post Types
Plugin URI: https://wordpress.org/plugins/bulk-password-protect-posts/
Description: Bulk Password Protect Post Types enables you to easily password protect all posts from selected post type.
Author: Bruno Kos
Version: 1.0
Author URI: http://bbird.me/

Text Domain: pass_protect_all
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

*/

// Making sure to direct access to this file
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function pass_protect_all_load_textdomain() { // Loads the plugin's translated strings
  load_plugin_textdomain( 'pass_protect_all', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

add_action( 'init', 'pass_protect_all_load_textdomain' );

// Register menu option for the plugin
add_action( 'admin_menu', 'pass_protect_all_add_admin_menu' ); // Add extra submenus and menu options to the admin panel
add_action( 'admin_init', 'pass_protect_all_settings_init' );

// Add a link to the settings page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pass_protect_all_settings_link' );
function pass_protect_all_settings_link( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/options-general.php?page=password_protect_all_posts">Settings</a>'
		),
		$links
	);
}

// Add option pages
function pass_protect_all_add_admin_menu(  ) { 

    // add_options_page is used to add an options page to the "Settings" menu
    
	add_options_page( 'Bulk Password Protect Settings',   // The text to be displayed in the title tags of the page when the menu is selected
                      'Bulk Password Protect',            // The text to be used for the menu
                      'manage_options',                   // The capability required for this menu to be displayed
                      'password_protect_all_posts',       // The slug name to refer to this menu by 
                      'pass_protect_all_options_page' );  // The function to be called to output the content for this page (submit code)

}

// Initialize plugin settings

function pass_protect_all_settings_init(  ) { 

	add_settings_section(
		'pass_protect_all_settings_section',                                    // Section ID
		__( 'Bulk Password Protect Post Types Settings', 'pass_protect_all' ),  // Section title
		'pass_protect_all_settings_section_callback',                           // Section content function
		'pass_protect_all_settings_page'                                        //  The menu page on which to display this section
	);

	add_settings_field( 
		'pass_protect_all_select_types',                             // Settings ID
		__( 'Post Type', 'pass_protect_all' ),                       // Settings title
		'pass_protect_all_select_types_render',                      // Settings content function
		'pass_protect_all_settings_page',                            //  The menu page on which to display this setting
		'pass_protect_all_settings_section',                         //  Section on which to display this setting
             array( 
            'pass_protect_all_select_types'                          // Option ID, arguments that are passed to the $callback function.
        )
	);
    
    	add_settings_field( 
		'pass_protect_all_pass_field', 
		__( 'Choose password for posts', 'pass_protect_all' ), 
		'pass_protect_all_pass_field_render', 
		'pass_protect_all_settings_page', 
		'pass_protect_all_settings_section' ,
                  array( 
            'pass_protect_all_pass_field' // Option ID
        )  
	);
        
        register_setting( 'pass_protect_all_settings_page', 'pass_protect_all_select_types' );   // A settings group name, the name of an option to sanitize and save
        register_setting( 'pass_protect_all_settings_page', 'pass_protect_all_pass_field' );


}



function pass_protect_all_select_types_render($args)  { // Function which pulls post types into array and outputs as checkbox
    
    echo '<p>' . __( 'Choose one or more post types. All posts within selected will be password-protected.', 'pass_protect_all' ) . '</p><br>';
    
    
    $options     = get_option($args[0]);
    $pag         = pass_protect_all_select_types;
    $post_types  = get_post_types();
    $html        = '';
  
    foreach ($post_types  as $post_type) {      
    if( is_array($options)):
    $checked = in_array($post_type, $options) ? 'checked="checked"' : '';
    endif;
    $html .= sprintf( '<input type="checkbox" id="%1$s[%2$s]" name="%1$s[]" value="%2$s" %3$s />', $pag, $post_type, $checked );
    $html .= sprintf( '<label for="%1$s[%3$s]"> %2$s</label><br>', $pag, $post_type, $post_type );
    }
    $html .= sprintf( '<span class="description"> %s</label>', '' );
    echo $html;
}

function pass_protect_all_pass_field_render($args) {  // Function which renders password field
    
    echo '<p>' . __( 'Make sure to set password, otherwise posts will not be (for obvious reasons) protected.', 'pass_protect_all' ) . '</p><br>';
    
   $option = get_option($args[0]);
   echo '<input type="text" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" placeholder="Password" />';
   
}


function pass_protect_all_set_password($post_object) { //Function which sets password for chosen post types
     
    $chosen_post_types = get_option("pass_protect_all_select_types"); 
    $post_password     = get_option("pass_protect_all_pass_field"); 
     if( is_array($chosen_post_types)):
     foreach ($chosen_post_types as $types) {
         if ($post_object->post_type==$types) {
		  $post_object->post_password = $post_password;
	}   
    }
    endif;
}
add_action('the_post', 'pass_protect_all_set_password');


function pass_protect_all_settings_section_callback(  ) { 

	// do nothing

}

function pass_protect_all_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

	    <?php
		settings_fields( 'pass_protect_all_settings_page' );       // Output nonce, action, and option_page fields for a settings page.      
		do_settings_sections( 'pass_protect_all_settings_page' );  // Prints out all settings sections added to a particular settings page.
		submit_button();
		?>

	</form>
	<?php

}

function your_prefix_activate(){
    register_uninstall_hook( __FILE__, 'your_prefix_uninstall' );
}
register_activation_hook( __FILE__, 'your_prefix_activate' );
 
// And here goes the uninstallation function:
function your_prefix_uninstall(){
    //  codes to perform during unistallation
}