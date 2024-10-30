<?php
/*
Plugin Name: ClickGUMSHOE
Plugin URI: https://clickgumshoe.com/
Description: Ads lock screen show first screen when visitor visit your website from Google Ads (Adwords).
Version: 1.0.4
Author: chongclicktac
Author URI: https://chongclicktac.com/
License: GPLv2 or later
Text Domain: adlock
*/

include_once('lock.php');

function hcgs_plugin_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=hcgs_settings">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}

add_filter( "plugin_action_links_".plugin_basename( __FILE__ ), 'hcgs_plugin_add_settings_link' );

function hcgs_plugin_activate() {
	if(!file_exists(wp_upload_dir().'/clickgumshoe_uploads')) mkdir(wp_upload_dir().'/clickgumshoe_uploads', 0777);

	if(version_compare(phpversion(), HCGS_MIN_PHP_VERSION) < 0) {
		return new WP_Error( 'broke', "Require PHP minimum of ". HCGS_MIN_PHP_VERSION );
	}
}
register_activation_hook( __FILE__, 'hcgs_plugin_activate' );
