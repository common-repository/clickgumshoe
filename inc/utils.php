<?php
//utils
/**
 * @param $tab
 */
function hcgs_load_tab($tab) {
    if(file_exists(HCGS_DIR. '/admin/tabs/'.$tab. '.php'))
    include_once (HCGS_DIR. '/admin/tabs/'.$tab. '.php');
}

/**
 * @param $name
 * @param string $default
 * @return mixed|null|void
 */
function hcgs_option($name='', $default= '') {
    //if($name) return AdminPageFramework::getOption( 'HCGS_Settings_page', array($group, $name), $default );
    $values = AdminPageFramework::getOption( 'HCGS_Settings_page' );
    if(isset($values[$name])) return $values[$name];
    else return $default;
}

if(!function_exists('hcgs_randomString')) :
function hcgs_randomString($length=10, $prefix='') {

    $keys = array_merge(range(0,9), range('a', 'z'));
    $key='';
    for($i=0; $i < $length; $i++) {

        $key .= $keys[array_rand($keys)];

    }
    return $prefix.$key;
}
endif;

function hcgs_is_cli() {
	return php_sapi_name() == "cli";
}

if(!function_exists('hcgs_isSSL')) :
function hcgs_isSSL() {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') return true;
  if(isset($_ENV['HTTPS']) && $_ENV['HTTPS']=='on') return true;
  //on heroku
  if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https') return true;
  return false;
}
endif;

if(!function_exists('hcgs_diff_host')) :
function hcgs_diff_host($url1, $url2) {
	$p1=parse_url($url1);
	$p2=parse_url($url2);
	if($p1['host']!==$p2['host']) return true;
}
endif;

if(!function_exists('hcgs_is_diff_url')) :
function hcgs_is_diff_url($url1, $url2) {
	$p1=parse_url($url1);
	$p2=parse_url($url2);//print_r($p1);print_r($p2);

	if(isset($p1['path'])) $p1['path'] = trim($p1['path'],'/');
	else $p1['path']='';
	if(isset($p2['path'])) $p2['path'] = trim($p2['path'],'/');
	else $p2['path']='';

	if(!isset($p1['query'])) $p1['query']='';
	if(!isset($p2['query'])) $p2['query']='';

	if($p1['scheme']!==$p2['scheme']) return true;
	if($p1['host']!==$p2['host']) return true;
	if($p1['path']!==$p2['path']) return true;
	if($p1['query']!==$p2['query']) return true;
	return false;
}
endif;
//wp_doing_ajax
if(!function_exists('hcgs_is_ajax')):
function hcgs_is_ajax() {
	return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
endif;
function hcgs_pageWasRefreshed() {
	$pageWasRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
	return $pageWasRefreshed;
}
function hcgs_ajax_result(array $data) {
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($data);
	die();
}

if(!function_exists('hcgs_set_no_cache_header')):
function hcgs_set_no_cache_header() {
	header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	//header("Connection: close");
}
endif;
function hcgs_fake_value_track($query=true) {
	$campIds = get_option('_had_campaigns');
	$params = array(
		'gclid'=> hcgs_randomString(),
		'lpurl'=> 'https://'.$_SERVER['SERVER_NAME'],
		'network'=>'g',
		'device'=>'c',
		'devicemodel'=>'',
		'keyword'=> isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST']:'',
		'matchtype'=>'e',
		'creative'=> mt_rand(0, mt_getrandmax() - 1),
		'placement'=>'',
		'campaignid'=> $campIds && count(array_filter($campIds))? hcgs_pick_one($campIds): '1014894911',
		'adgroupid'=> mt_rand(0, mt_getrandmax() - 1),
		'loc_physical_ms'=>'',
		'random'=> mt_rand(0, mt_getrandmax() - 1),
		'adposition'=> '1t1',
	);
	return $query? http_build_query($params): $params;
}
//send remote log
/*if(!function_exists('send_remote_syslog')) :
function send_remote_syslog($message, $component = "web", $program = "next_big_thing") {
	if(!get_api('PAPERTRAIL_HOSTNAME') || !get_api('PAPERTRAIL_PORT')) return;
	if(isset($_SERVER['SERVER_NAME'])) $program = $_SERVER['SERVER_NAME'];
  $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
  foreach(explode("\n", $message) as $line) {
    $syslog_message = "<22>" . date('M d H:i:s ') . $program . ' ' . $component . ': ' . $line;
    socket_sendto($sock, $syslog_message, strlen($syslog_message), 0, get_api('PAPERTRAIL_HOSTNAME'), get_api('PAPERTRAIL_PORT'));
  }
  socket_close($sock);
}
endif;*/

function hcgs_getImagebase64Size($base64) {
	if(strpos($base64, ';base64')!==false){
		$base64 = explode(';base64,', $base64);
		$base64 = $base64[1];
	}
	$s = getimagesize('data://application/octet-stream;base64,'. $base64);
	return $s['bits'];
}
function hcgs_base64_to_image($base64_string, $output_file) {
    // open the output file for writing
    $ifp = fopen( $output_file, 'wb' ); 

    // split the string on commas
    // $data[ 0 ] == "data:image/png;base64"
    // $data[ 1 ] == <actual base64 string>
    $data = str_replace('data:image/jpeg;base64,', '', $base64_string);
    //$data = explode( ',', $base64_string );
    $data = str_replace(' ', '+', $data);

    // we could add validation here with ensuring count( $data ) > 1
    fwrite( $ifp, base64_decode( $data ) );	//$data[ 1 ]

    // clean up the file resource
    fclose( $ifp ); 

    return $output_file; 
}

function hcgs_enable_feature($name) {
	$features= array('heatmap'=>0, 'emulator'=> 1);
	return !isset($features[$name]) || $features[$name];
}

function hcgs_deleteDir($dirPath, $itself=false) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '/*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            hcgs_deleteDir($file, true);
        } else {
            unlink($file);
        }
    }
    if($itself) rmdir($dirPath);
}
function hcgs_format_tel($tel) {
	$lz = substr($tel,0,1)=='0';
  	return ($lz?'0':'').str_replace(',','.',number_format($tel));
}
function hcgs_renderList($dt, $arg=array()) {
	$exclude = isset($arg['exclude'])? $arg['exclude']: array();
	$att = isset($arg['att'])? hcgs_htmlAttrs($arg['att']): '';
	$style = isset($arg['style'])? $arg['style']: 'html';
	
	$ui= ($style=='html'? "<ul $att>":'');
	if(is_array($dt))
	foreach($dt as $k=> $v) {
		if(count($exclude) && in_array($k, $exclude)) continue;
		if($style=='html') $ui.="<li><strong>{$k}</strong>: {$v}</li>";
		else $ui.= "- {$k}: $v\n";
	}
	if($style=='html') $ui.='</ul>';
	if(is_array($dt) && count($dt)) return $ui;
}
function hcgs_array_exclude_keys($arr, $keys=[]) {
    #foreach($arr as $k=>$v) if(in_array($k, $keys)) unset($arr[$k]);
    foreach($keys as $k) if(isset($arr[$k])) unset($arr[$k]);
    return $arr;
}
function hcgs_htmlAttrs($attrs) {
	$ui='';
	foreach ($attrs as $key => $value) {
		$ui.= $key. '="'. addslashes($value) .'" ';
	}
	return $ui;
}
