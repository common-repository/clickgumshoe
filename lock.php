<?php
//if using ioncube to encode this file, so move plugin header to plugin.php
#@session_start();
// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}
define('HCGS_MIN_PHP_VERSION', '5.6.0');
define('HCGS_URL', plugins_url('', __FILE__));
define ('HCGS_DIR', plugin_dir_path(__FILE__ )) ;
$ajax_url = admin_url( 'admin-ajax.php' );//plugins_url('', __FILE__). '/ajax.php';
//define('HCGS_TEST_MODE', 1);
//define( 'W3TC_DYNAMIC_SECURITY', md5( rand( 0, 999999 ) ) );	//w3c paid

include_once ('libs/vendor/autoload.php');
if(!class_exists('AdminPageFramework_Registry')) require 'libs/apf/admin-page-framework.php';

//if(!is_admin()) {
include __DIR__.'/html/libs/config.php';
//}
include_once 'inc/functions.php';
include_once 'admin/settings.php';

if(hcgs_isSSL()) {
	define('HCGS_MANAGER', 'https://clickgumshoe.com/');	//https://hoangweb-ads-manager.herokuapp.com/
}
else {
	if(HCGS_TEST_MODE) define('HCGS_MANAGER', 'http://s2.click-gumshoe.me');	//test
	else define('HCGS_MANAGER', 'https://clickgumshoe.com/');	//http://hoangweb-ads-manager.herokuapp.com/
}

if(!hcgs_is_ajax()) {
	//be sure same cookie domain, since we use cdn.domain.tld
	$url = parse_url($ajax_url);
	if(isset($_SERVER['HTTP_HOST']) && $url['host']!==$_SERVER['HTTP_HOST']) {
	    $url['host'] = $_SERVER['HTTP_HOST'];
	    $ajax_url = $url['scheme'].'://'.$url['host'].$url['path'];
	    if(isset($url['query'])) $ajax_url.= '?'.$url['query'];
	}
	define('HCGS_AJAX_URL', $ajax_url);
}

/*add_filter('template_include', 'hcgs_template_include', 50);	//no
function hcgs_template_include($file) {
	if(!is_cli() && !(int)hcgs_option('popup') && function_exists('is_from_adwords') && is_from_adwords()) {
		$GLOBALS['standalone_page'] = 1;
		$file = __DIR__.'/html/adscreen.php';
	}
	return $file;
}*/

/*
function ad_plugin_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=hcgs_settings">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}

add_filter( "plugin_action_links_".plugin_basename( __FILE__ ), 'ad_plugin_add_settings_link' );

function ad_plugin_activate() {
	mkdir(wp_upload_dir().'/clickgumshoe_uploads', 0777);

	if(version_compare(phpversion(), HCGS_MIN_PHP_VERSION) < 0) {
		return new WP_Error( 'broke', "Require PHP minimum of ". HCGS_MIN_PHP_VERSION );
	}
}
register_activation_hook( __FILE__, 'ad_plugin_activate' );
//a();	#test papertrailapp
*/