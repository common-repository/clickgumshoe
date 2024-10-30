<?php
//require_once 'utils.php';

//add_action('init', 'session_start');strpos($_SERVER['REQUEST_URI'], 'random')!==false &&

//utils
/**
 * @param $tab
 */
function hcgs_load_tab($tab) {
    if(file_exists(HCGS_DIR. '/admin/tabs/'.$tab. '.php'))
    include_once (HCGS_DIR. '/admin/tabs/'.$tab. '.php');
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

function hcgs_enable_feature($name) {
	$features= array('heatmap'=>0, 'emulator'=> 1);
	return !isset($features[$name]) || $features[$name];
}
//@deprecated: this plugin should not be cache
#add_action('wp_enqueue_scripts', 'hcgs_enqueue_asset');
function hcgs_enqueue_asset() {
	wp_enqueue_script('hcgs-plugins', hcgs_asset(HCGS_URL. '/html/asset/plugins.js',1), array('jquery'));
	wp_enqueue_script('hcgs-clickgs', hcgs_asset(HCGS_URL. '/html/asset/clickgs.js',1), array('hcgs-plugins'));

	wp_enqueue_style('hcgs_style', hcgs_asset(HCGS_URL. '/html/asset/clickgs.css'));
	if(hcgs_option('heatmap_tracking')){
		$custom_css='#heatmap-canvas{
    position:absolute;
  left:0;
  top:0;
  visibility: hidden;/*do not use display:none*/
  z-index: 1000;
  width: 100% ;
}
* { 
    pointer-events:none;
}
span,div,p,textarea,select,input,iframe{
    pointer-events:auto;
}
a,button {
  pointer-events:auto;
  cursor:pointer;
}
a:hover{
    text-decoration: underline;
}
body {
  pointer-events:auto;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}';
	wp_add_inline_style( 'hcgs-custom-style', $custom_css );
	}
	wp_localize_script('hcgs-plugins', 'hcgs_lock', array('ajax_url' => admin_url( 'admin-ajax.php' ),'adwords_url'=> HCGS_MANAGER,'hit_submit_url'=> HCGS_AJAX_URL.'?action=hcgs_lock_submit&nonce='.wp_create_nonce("user_hit_button_nonce") ));
}

add_action('admin_print_styles', 'hcgs_print_styles');
function hcgs_print_styles() {
	echo '<style>
	.myspinner {
		background: url("'.admin_url().'/images/wpspin_light.gif") no-repeat !important;
		background-size: 16px 16px;
		/*display: none;
		float: right;*/
		opacity: .7;
		filter: alpha(opacity=70);
		/*width: 16px;*/
		height: 16px;
		margin: 5px 5px 0;
		/*position: fixed;*/
	    /*width: 100%;*/
	    height: 100%;
	    top: 0px;
	    z-index: 1000000000000;
	    display:inline-block;
	}

	</style>';
}

#add_action('wp_enqueue_scripts', 'hcgs_enqueue_assets');
function hcgs_enqueue_assets() {
	#wp_enqueue_style('hcgs-style', HCGS_URL.'/asset/style.css');
}
add_action('wp_head', 'hcgs_print_assets',20, 100);
function hcgs_print_assets() {
	#echo '<script type="text/javascript">var hcgs_lock = '.json_encode(array('ajax_url' => admin_url( 'admin-ajax.php' ),'adwords_url'=> HCGS_MANAGER,'hit_submit_url'=> HCGS_AJAX_URL.'?action=hcgs_lock_submit&nonce='.wp_create_nonce("user_hit_button_nonce") )).';</script>';

	echo '<script src="'.hcgs_asset(HCGS_URL. '/html/asset/plugins.js',1).'"></script>';
	echo '<script src="'.hcgs_asset(HCGS_URL. '/html/asset/clickgs.js',1).'"></script>';
	echo '<link rel="stylesheet" type="text/css" href="'.hcgs_asset(HCGS_URL. '/html/asset/clickgs.css').'">';

}

if( !has_action('wp_head','hcgs_print_head') && empty($GLOBALS['not_allow_clickgs']) && !hcgs_is_ajax()) :
function hcgs_print_head($force=0) {
	#remove_action('wp_head',__FUNCTION__);
	
	if(isset($GLOBALS['run_hcgs_print_head']) && !$force) return;#hcgs_log_to_file(print_r($_SERVER['HTTP_REFERER'],1));
	$GLOBALS['run_hcgs_print_head']=1;
	@session_start();
	if(!hcgs_is_cli() && function_exists('hcgs_is_from_adwords') 
		&& ( 
			(hcgs_is_from_adwords() /*&& is_show_cover_for_ip()*/) || hcgs_get_visitor_data('is_from_adwords', false) || $force
		)
	) {
		$h = apache_request_headers();
		$adlock_data = get_option('_had_adlock_data');
		$ga_dimension3 = get_option('_had_ga_dimension3');
		$ga_dimension1 = get_option('_had_ga_dimension1');
		$active_servers = hcgs_get_active_servers();//$GLOBALS['_had_active_servers'] = 
		if(!$adlock_data || !$active_servers || isset($h['X-Moz'])) {
			//clear data
			if(!isset($h['X-Moz'])) hcgs_clear_user_data();
			#if(HCGS_TEST_MODE)hcgs_log_to_file('empty adlock_data or active_servers');
			return ;
		}
		#$ref = isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']: '';if($ref)$ref = ((object)parse_url($ref))->host;
		#$domain = hcgs_getSiteName('', false);
		$ip = hcgs_getClientIP();
		$submit_ajax_url = HCGS_AJAX_URL.'?action=hcgs_lock_submit&nonce='.wp_create_nonce("user_hit_button_nonce") ;
		
		$GLOBALS['hw_adlock_data'] = $adlock_data;
		/*$GLOBALS['_had_ga_dimension1'] = $ga_dimension1;
		$GLOBALS['_had_ga_dimension3'] = $ga_dimension3;*/
		$is_send = hcgs_is_from_adwords(true)? 1: (hcgs_is_from_search(1) ||hcgs_is_debug_ad());#!hcgs_visitor_is_done();
		#if(HCGS_TEST_MODE && $is_send)$is_send=0;#rand(0,1);
		$GLOBALS['_had_show_popup'] = $show_popup = (int)hcgs_option('popup') && $is_send;
		$show_cover = hcgs_is_show_cover_for_ip($ip);
		$GLOBALS['_had_is_show_popup'] = $is_show_popup = ($show_cover && $show_popup && !hcgs_visitor_is_done($ip));/*$send_check*/

		$_lock = ['ajax_url'=> HCGS_AJAX_URL, 'hit_submit_url'=> $submit_ajax_url, 'adwords_url'=>HCGS_MANAGER, 'nonce_userdata'=> wp_create_nonce("authorize_service_nonce"),'v_php'=>1,];
		
		if( !$force && hcgs_is_from_adwords() && hcgs_is_first_user_session() && $is_send && !hcgs_pageWasRefreshed()) {
			#if($ref != $domain ) {
				$data = array('ip'=>$ip, 'active_servers'=> $active_servers,'show_popup'=> $show_popup? 1:0, 'wait_for_replace'=>1, 'timeout'=> 40);
				if(!hcgs_is_organic_test()) $data['valueTrack'] = hcgs_getValueTrack();//$GLOBALS['_had_valueTrack'] = 
				if(!$show_popup) hcgs_update_visitor( array('click'=>1), $ip);

				$data = hcgs_send_check_IP($data/*, 'checking_ip_callback'*/);
			#}
			
			if(!$data['no_send']) $send_check = true;
			unset($data['no_send']);unset($data['wait_for_replace']);unset($data['timeout']);
			$_lock['data'] = hcgs_array_exclude_keys($data,['api']);
			$_lock['send_check'] = !empty($send_check);
		}
		
		//if(!isset($h['X-Moz'])) {
			//, data: '.json_encode($adlock_data).'
			echo '<script type="text/javascript">/*[clickgs-keep-js]*/
			var hcgs_lock = '.json_encode($_lock).';
			</script>';
			include dirname(__DIR__). '/html/layout/top_head.php';
			#include __DIR__. '/html/layout/head_wp.php';
		//}
	}
}
add_action('wp_head', 'hcgs_print_head',10, 1);
endif;

if(!has_action('wp_footer','hcgs_print_footer') && empty($GLOBALS['not_allow_clickgs']) && !hcgs_is_ajax()) :
add_action('wp_footer', 'hcgs_print_footer', 0);
function hcgs_print_footer() {
	#remove_action('wp_footer',__FUNCTION__);
	if(isset($GLOBALS['run_hcgs_print_footer'])) return;//echo ('=>a'.is_from_adwords());
	$GLOBALS['run_hcgs_print_footer']=1;
	
	#if(!hcgs_is_cli() && isset($GLOBALS['hw_adlock_data']))
	//if(function_exists('hcgs_is_from_adwords') && (hcgs_is_from_adwords() || hcgs_get_visitor_data('hcgs_is_from_adwords', false)) ) {
	//if(hcgs_is_from_search()/*!empty($GLOBALS['_had_is_show_popup'])*/) {
		include_once dirname(__DIR__).'/html/adscreen.php';
	//}
}
endif;

/**
	Put in wp_footer hook
*/
function hcgs_conversion_integration() {
	$referer = hcgs_referrer();
	$chat_srv = hcgs_option('chat_service');
	//if(1) {//||$referer
	$User = array('info'=>'');
	remove_action('wp_footer',__FUNCTION__);

	//from ads click
	if((hcgs_is_from_adwords() || hcgs_get_visitor_data('is_from_adwords',0)) 
		&& !hcgs_is_organic_test()) {
		
		$track_data = hcgs_getValueTrack();
		$User['info'] .= '[U][B]Adwords Information[/B][/U]:[br]';
		$User['info'].= ' + IP : '. hcgs_getClientIP().'[br]';
		foreach($track_data as $k=> $v) {
			if($k=='lpurl') {
				//$v="[url=".$v."]".$v."[/url]";
				continue;
			}
			//$v = get_ad_param($v, $k);	//not show full data in bitrix
			if($v!=='') $User['info'] .= " + $k : $v"." [br]";
		}
		//no use url, will truncate other data
		//$msg.= "From: [url=".$referer."]".$referer."[/url] [br]";
		#$msg.= "From: $referer "."[br]";
		#$msg.= "Page: [url={page.url}]{page.title}[/url] [br]";
		
	}
	else {
		//from organic search
		//$msg.= "From: [url=".$referer."]".$referer."[/url] [br]";
		$User['info'] .= "From search: $referer "."[br]";
		//$msg .= "Page: [url={page.url}]{page.title}[/url] [br]";

	}

	//if(1||$chat_srv=='bitrix') {
		$User['user'] = array(
			'hash' => hcgs_gclid(), 
			'referer'=> 'Adwords #'
		);
		
		$User['firstMessage'] = $User['info'];
	//}
	
	?>
	<script type="text/javascript">
		if(typeof adsInitialize!=='function')function adsInitialize(cb,test) {
			var i=0;
			if(!test) test=['HW_IO'];
			if(typeof test=='string') test=[test];
			var tm=setInterval(function(){
				var c=1;
				for(var j=0;j<test.length;j++) if(typeof eval('try{'+test[j]+'}catch(e){}')==='undefined') {c=0;break;}
				if(c || i++>100) {
					clearInterval(tm);
					cb();
				}
			},100);
		}
	adsInitialize(function(){
		HW_IO.log('%c integration conversion','color:#6600ff');//if(typeof HW_IO=='undefined') return;
		//#### livechat conversion
		var livechat = '<?php echo $chat_srv?>', user = <?php echo str_replace('\/','/',json_encode($User)) ?>;
	  	
	  	//for bitrix chat
		if(livechat=='bitrix') {
			user.firstMessage = user.firstMessage.replace('{page.url}', location.href);
		  	user.firstMessage = user.firstMessage.replace('{page.title}', document.title || location.href);

			HW_IO.utils.livechat.bitrix.init(user);	//init user

			function send_hello_client() {
		  		HW_IO.utils.livechat.bitrix.send_hello_client([
  							'Chào bạn',
  							'Chào bạn! Mình muốn tư vấn về dịch vụ của bên bạn',
  							'Minh cần tư vấn về thiết kế / tối ưu website?',
  							'Cho mình hỏi về dịch vụ thiết kế web Wordpress của bên bạn?'
  						]);
		  	}
		  	if(0)adsInitialize(function(){
		  		if(typeof BX=='undefined') return;
		  		if(!HW_IO.hasEvent('close_popup')) {
		  			send_hello_client();
		  		}
		  		else HW_IO.addEvent('close_popup', send_hello_client, null,true);
		  		
		  	}, ['HW_IO','BX']);
		  	/*adsInitialize(function(){	@deprecated, wrong
		  		if( !HW_IO.class.Conversion.chat().isFirstMessageSent() && !user.close_popup)
		  		if(window.BX && window.BX.SiteButton) window.BX.SiteButton.hide();
		  		/*BX.SiteButton.addEventHandler('form-init',function(form){
				    console.log('form 1');
				});*/
		  	//},'BX');
		}
		if(livechat=='tawk.to') {
			HW_IO.utils.livechat.Tawk.init();
		}
		if(livechat=='zopim') {
			HW_IO.utils.livechat.Zopim.init(function() {
				if(typeof $zopim!=='undefined') $zopim.livechat.setNotes('<?php echo $User['info'] ?>');
			});
		}
		if(livechat=='drift') {
			HW_IO.utils.livechat.Drift.init();
		}
		if(livechat=='chatra') {
			HW_IO.utils.livechat.Chatra.init(user/*, '.cgs-chatra-icon'*/);
		}
		if(livechat == 'freshchat') {
			HW_IO.utils.livechat.Freshchat.init(user);
		}
		if(livechat == 'chaport') {
			HW_IO.utils.livechat.Chaport.init();
		}
		if(livechat == 'olark') {
			HW_IO.utils.livechat.Olark.init();
		}
		if(livechat == 'subiz') {
			HW_IO.utils.livechat.Subiz.init();
		}
		if(livechat == 'vchat') {
			HW_IO.utils.livechat.Vchat.init();
		}

		jQuery(document).ready(function($){
			//#### form data for conversion
			if(typeof HW_IO!=='undefined' && HW_IO.get('test_mode')) {	//@deprecated
				HW_IO.class.Conversion.form.setToken('input[name="formdata"].wpcf7-hidden');					
			}
			$('input.wpcf7-submit,button.wpcf7-submit,.cgs-submit-form').on('click', function(){
				HW_IO.class.Conversion.form.submitForm();
			});
			//#### client make a phone call as conversion
			HW_IO.class.Conversion.integration.phonecall();
		  		
		});  
	}, ['HW_IO']);
	
	</script>
	<?php
	
	do_action('conversion_embed_code');
}
add_action('wp_footer', 'hcgs_conversion_integration');

//add_action("wpcf7_before_send_mail", "hcgs_cf7_before_send_mail");
function hcgs_cf7_before_send_mail($wpcf7) {
	//$form_id = $wpcf7->id();
	//do not send the email
	$wpcf7->skip_mail = true;

	$submission = WPCF7_Submission::get_instance();
	if($submission){
      	$posted_data = $submission->get_posted_data();
      	//bitrix
      	$queryUrl = '<!-- enter your bitrix rest -->';
      	$queryUrl.= 'crm.lead.add.json';

      	$queryData = http_build_query(array(
		 'fields' => array(
			 "TITLE" => $posted_data['your-name'],	//$_REQUEST['first_name'].' '.$_REQUEST['last_name'],
			 "NAME" => $posted_data['your-name'],
			 "LAST_NAME" => '',
			 "STATUS_ID" => "NEW",
			 "OPENED" => "Y",
			 "ASSIGNED_BY_ID" => 1,
			 "PHONE" => array(array("VALUE" => $posted_data['your-phone'], "VALUE_TYPE" => "WORK" )),
			 "EMAIL" => array(array("VALUE" => $posted_data['your-email'], "VALUE_TYPE" => "WORK" )),
			 "COMMENTS"=> hcgs_renderList(hcgs_array_exclude_keys($posted_data,array('_wpcf7','_wpcf7_version','_wpcf7_locale','_wpcf7_unit_tag','_wpcf7_container_post','formdata')),array('style'=>'html'))
		 ),
		 'params' => array("REGISTER_SONET_EVENT" => "Y")
		));
		 
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_SSL_VERIFYPEER => 0,
		  CURLOPT_SSL_VERIFYHOST => false,
		  CURLOPT_POST => 1,
		  CURLOPT_HEADER => 0,
		  CURLOPT_RETURNTRANSFER => 1,
		  CURLOPT_URL => $queryUrl,
		  CURLOPT_POSTFIELDS => $queryData,
		));
		 
		$result = curl_exec($curl);
		curl_close($curl);
		 
		$result = json_decode($result, 1);
		if (array_key_exists('error', $result)) hcgs_send_remote_syslog("Error saving lead: ".print_r($result,true), 'clickgumshoe');
		//return $result;
   	}
}
//add_filter('wpcf7_skip_mail','__return_true');

add_action('conversion_embed_code', 'hcgs_conversion_embed_code');
function hcgs_conversion_embed_code() {
	$livechat = hcgs_option('chat_service');
	$chat_embed = hcgs_option('chat_code');
	$tel = preg_replace('#[\s]+#','',hcgs_option('telephone'));

	if($livechat =='bitrix'){
		//insert code here
		echo $chat_embed;
	}
	else if($livechat== 'drift') {
		//insert code here
		echo $chat_embed;
	}else if($livechat== 'zopim') {
		//insert code here
		echo $chat_embed;
	}else if($livechat== 'tawk.to') {
		//insert code here
		echo $chat_embed;
	}
	else if($livechat== 'chatra') {
		//insert code here
		echo $chat_embed;
		/*echo '<span class="cgs-chatra-icon" title="Live chat"><span class="cgs-chatra-text">
	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 30 30" style="enable-background:new 0 0 30 30;" xml:space="preserve"><g><g><g><path d="M30,18.3c0-2.683-1.878-5.024-4.674-6.291c0.165,0.585,0.26,1.189,0.277,1.809c0.279,0.166,0.549,0.345,0.801,0.54 c1.41,1.088,2.188,2.487,2.188,3.942c0,1.454-0.775,2.854-2.188,3.94c-1.547,1.193-3.627,1.851-5.856,1.851 c-1.503,0-2.938-0.3-4.188-0.859c-0.776,0.153-1.583,0.25-2.414,0.284c1.705,1.281,4.033,2.072,6.602,2.072 c1.649,0,3.199-0.324,4.548-0.897l3.441,1.756l-0.695-3.511C29.189,21.676,30,20.061,30,18.3z" fill="#FFFFFF"/><path d="M24.624,12.837c-0.019-0.619-0.112-1.224-0.276-1.808c-1.201-4.274-6.133-7.476-12.034-7.476 C5.513,3.554,0,7.806,0,13.051c0,2.295,1.055,4.398,2.812,6.041l-0.906,4.572l4.483-2.287c1.758,0.746,3.777,1.172,5.925,1.172 c0.219,0,0.437-0.006,0.653-0.014c0.829-0.033,1.637-0.131,2.415-0.285c5.316-1.051,9.246-4.771,9.246-9.199 C24.627,12.98,24.626,12.909,24.624,12.837z M19.945,18.187c-1.867,1.438-4.33,2.28-6.993,2.396 c-0.211,0.011-0.424,0.016-0.639,0.016c-0.412,0-0.819-0.018-1.222-0.053c-2.438-0.204-4.679-1.023-6.408-2.357 c-1.837-1.417-2.849-3.241-2.849-5.136c0-1.895,1.012-3.719,2.849-5.136c2.015-1.554,4.725-2.41,7.63-2.41 c2.906,0,5.617,0.856,7.63,2.41c0.912,0.703,1.619,1.506,2.103,2.368c0.306,0.547,0.521,1.118,0.64,1.702 c0.071,0.351,0.107,0.707,0.107,1.065C22.793,14.946,21.782,16.77,19.945,18.187z" fill="#FFFFFF"/></g><g><circle cx="16.682" cy="13.08" r="1.347" fill="#FFFFFF"/><circle cx="12.314" cy="13.08" r="1.347" fill="#FFFFFF"/><circle cx="7.946" cy="13.08" r="1.348" fill="#FFFFFF"/></g></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>
</span></span>';*/
	}
	else {
		echo $chat_embed;
	}
	//embed bitrix chat embed code
	if($tel/*|| hcgs_is_mobile()*/) {
	?>
	<!-- <span itemprop="telephone" class="phone-call"><a href="javascript:void(0)" class="button test-phone-call">Gọi cho tôi!</a></span> -->
	<!-- <span itemprop="telephone" class="phone-call"><a href="tel:+841663930250" class="button ">Gọi cho tôi!</a></span> -->
	<!-- Start Quick Call Buttons by clickgumshoe -->
      <div class='cgs-quick-call-button'></div>
      <div class='cgs-call-now-button'>
        <div><?php if(trim($tel)){?><p class='cgs-call-text' itemprop="telephone"> <strong><?php echo hcgs_format_tel($tel) ?></strong> </p><?php }?>
          <a href='tel:<?php echo $tel?>' title='Call Now' class="cgs-phone-call">
          <div class='cgs-quick-alo-ph-circle'></div>
                    <div class='cgs-quick-alo-ph-circle-fill'></div>
                    <div class='cgs-quick-alo-ph-img-circle'></div>
          </a>
        </div>
      </div>
      
      <!-- /End Quick Call Buttons By clickgumshoe -->
	<?php
	}
}
