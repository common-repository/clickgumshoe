<?php
class HCGS_GeneralSettings extends HCGS_Setting_Tab { 
    const SETTINGS_GROUP = 'general'; 
    public static $instance; 

    function __construct() { 
    }

    public function get_fields_definition() { 
        $html='<span id="site_check" class="loading myspinner"></span>'; 
        $html.='&nbsp;&nbsp;<a class="button" id="btn_ad_authorize">Authorize</a>'; 
        $fields = array( 
            array( 
                'field_id'=> 'status', 
                'type'=> 'string', 
                'title'=> 'Status', 
                'description'=> $html 
            ), 
            array( 
                'field_id'=> 'site_token', 
                'type'=> 'text', 
                'title'=> 'Token' 
            ), 
            array( 
                'field_id'=> 'text_test', 
                'type'=> 'string', 
                'title'=> '<h3><!-- Test -->Option</h3>' 
            ), 
            array( 
                'field_id'=> 'ga_connect', 
                'type'=> 'text', 
                'title'=> 'Google Analystic ID', 
                'default'=> '', 
                'description'=> 'Note: Adding custom dimension in GA to help better analystic.' 
            ), 
            /*array( 
                'field_id'=> 'tracking_url', 
                'type'=> 'text', 
                'title'=> 'Tracking URL', 
                'default'=> '', 
                'description'=> 'Webhook URL, where you receive data input from user.' 
            ),*/ 
            array( 
                'field_id'=> 'popup', 
                'type'=> 'checkbox', 
                'title'=> 'Show Popup', 
                'default'=> true 
            ),
            array(
                'field_id'=> 'telephone',
                'type'=> 'text',
                'title'=> 'Call Now'
            ),
            array(
                'field_id'=> 'chat_service',
                'type'=> 'select',
                'title'=> 'Live Chat',
                'label'=> array(
                    'tawk.to'=> 'Tawk.to',
                    'bitrix'=> 'Bitrix24',
                    'drift'=> 'Drift',
                    'zopim'=> 'Zendesk chat',
                    'chatra'=> 'Chatra',
                    'freshchat'=> 'Freshchat',
                    'chaport'=> 'Chaport',
                    'olark'=> 'Olark',
                    'subiz'=> 'Subiz',
                    'vchat'=> 'vChat'
                )
            ),
            array(
                'field_id'=> 'chat_code',
                'type'=>'textarea',
                'title'=> 'Chat embed code',
                'default'=> '',
                'description'=> 'Note: Insert your chat code here. (optional)'
            ),
            
            /*array( 
                'field_id'=> 'popup_email', 
                'type'=> 'checkbox', 
                'title'=> 'Show Popup email', 
                'default'=> false 
            ), */
            
        ); 
        if(hcgs_enable_feature('heatmap')) {
            $fields[] = array( 
                'field_id'=> 'heatmap', 
                'type'=> 'checkbox', 
                'title'=> 'Heatmap tracking', 
                'default'=> false 
            );
        }
            
        $fields[] = array(
            'field_id'=>'tool',
            'type'=>'string',
            'description'=> '<span style="margin-right:10px"><a class="button" id="cgs_clean_cache" href="javascript:void(0)">Flush cache</a></span>'.
                '<span><a target="_blank" class="button test-clickgumshoe ads-click-link" href="'.site_url('/?_ad_debug=1&'. hcgs_fake_value_track()).'">Test</a></span>'.
                '<p style="margin-top:15px;position:absolute;top: 0px;right: 0px;"><span class="dashicons dashicons-editor-help"></span> <a href="https://chongclicktac.freshdesk.com/support/solutions/articles/43000011811-h%C6%B0%E1%BB%9Bng-d%E1%BA%ABn-c%E1%BA%A5u-h%C3%ACnh-website" target="_blank">Xem hướng dẫn</a></p>'
        );
        if(hcgs_enable_feature('emulator') && !empty($_REQUEST['_emulator'])) {
            $fields[]= array( 
                'field_id'=> 'test_from_organic_search', 
                'type'=> 'checkbox', 
                'title'=> 'Test From organic search', 
                'description'=> 'Check this to get track click from organic search engine ex: google, bing, yahoo..' 
            ) ;
            $fields[] = array( 
                'field_id'=> 'test', 
                'type'=> 'string', 
                'description'=> '<div>IP: <input type="text" name="ads_test_ip" id="ads_test_ip" placeholder="Test IP"/>&nbsp;'.
                    '<a href="javascript:void(0);" class="generate-test-ip btn-link">Generate IP</a> | <a href="/wp-admin/admin-ajax.php?action=hcgs_lock_debug" target="_blank" class="btn-link">Debug</a></div>'.
                    '<div><input type="checkbox" name="random_devid" id="random_devid" />&nbsp;Enable Browser</div>'.
                    //'<a href="javascript:void(0);" class="generate-test-ip btn-link">Generate IP</a></div>'.

                    '<a href="'.site_url('/?_ad_debug=1&'. hcgs_fake_value_track()).'" class="test-clickgumshoe ads-click-link" target="_blank">Test adwords click</a> | ' .
                    '<a href="'.site_url('/?_organic_test=1'/*&gclid=.RandomString()*/).'" class="test-clickgumshoe organic-click-link" target="_blank">Test organic click</a>'
                ) ;
        }
        else {
            $fields[] = array( 
                'field_id'=> 'test',
                'type'=> 'string', 
                'description'=> ''
            );
        }

        return $fields;
    } 

    public static function replyToAddFormElements($oAdminPage) { 
        $setting = self::$setting; 
        $tab = $setting->get_tabs(__CLASS__); 
        self::register_fields($oAdminPage, true, false); 
    } 

    public static function tab_info() { 
        return array('title'=> 'General', 'description' => ''); 
    } 

    public function do_html() { 
        $nonce = wp_create_nonce("authorize_service_nonce"); 
        $data = get_option('_had_adlock_data'); 
        $ip = hcgs_getClientIP();

        echo '<script src="'.HCGS_URL.'/asset/jquery.blockUI.js"></script>'; 
        echo '<script type="text/javascript">
        function site_check(callback) {
            jQuery.ajax({
                url: "'.rtrim(HCGS_AJAX_URL,'/').'?action=hcgs_rest&task=check_status",
                type: "post",
                dataType: "json",
                async:true,
                crossDomain:true,
                data: {site: location.hostname},
                success: function(resp) {
                    if(typeof callback=="function") callback(resp.data);
                    console.log(resp.data);
                }
            });
        }
        function generateIP() {
            var ip = (Math.floor(Math.random() * 255) + 1)+"."+(Math.floor(Math.random() * 255) + 0)+"."+(Math.floor(Math.random() * 255) + 0)+"."+(Math.floor(Math.random() * 255) + 0);
            return ip;
        }
        function showLoading(status, arg) {
            if(typeof status!=="undefined" && !status) return $.unblockUI();
            $.blockUI(Object.assign({ 
                message: "Vui lòng chờ...",//$("div.growlUI"), 
                fadeIn: 700, 
                fadeOut: 700, 
                timeout: 2000, 
                showOverlay: true, 
                centerY: true,
                centerX: true, 
                css: { 
                    width: "auto", 
                    //top: "100px", 
                    //left: "", 
                    //right: "10px", 
                    border: "none", 
                    padding: "5px", 
                    backgroundColor: "#000", 
                    "-webkit-border-radius": "10px", 
                    "-moz-border-radius": "10px", 
                    opacity: .6, 
                    color: "#fff"
                } 
            },arg||{})); 
        }
        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(";");
            for(var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == " ") {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }
        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires="+d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }
        function adlock_authorize_event() {
            var token = $("#site_token__0").val(), btn=$(this);
            var manager_url="'.rtrim(HCGS_MANAGER,'/').'";
            manager_url = manager_url.replace(/[\s\/]$/g,"");
            if(!token) {
                alert("Please fill token field ?");
                return;
            }
            btn.off("click", adlock_authorize_event).addClass("myspinner");
            showLoading();
            jQuery.ajax({
                url: "'. HCGS_AJAX_URL .'?action=hcgs_rest&task=userdata",
                type: "post",
                dataType: "json",
                async:true,
                crossDomain:true,
                data: {domain: location.hostname, token: token},
                success: function(data) {
                    if(!data ) {alert("Server Error !");return;}
                    data = data.data;
                    if( data.error) {
                        alert(data.msg);
                        return;
                    }
                    var dt = {}, _data={data: dt, nonce: "'.$nonce.'"},done=0;
                    if(data.data ) {
                        if(data.data.db_info) dt.db = data.data.db_info;/*console.log(data);*/
                        if(data.data.servers) dt.servers = data.data.servers;
                        if(data.data.pushover_token) dt.pushover_token = data.data.pushover_token;
                        if(data.data.cloudinary) dt.cloudinary = data.data.cloudinary;
                        if(data.data.ga_dimension3) _data.ga_dimension3 = data.data.ga_dimension3;
                        if(data.data.ga_dimension1) _data.ga_dimension1 = data.data.ga_dimension1;
                        if(data.data.campaigns) _data.campaigns = data.data.campaigns;
                    }
                    
                    jQuery.ajax({
                        url: "'. HCGS_AJAX_URL .'?action=hcgs_save_userdata&nonce='.$nonce.'",//_action
                        type: "POST",
                        dataType: "json",
                        data: _data,
                        success: function(dt) {
                            
                            if(++done==1) {
                                btn.on("click",adlock_authorize_event).removeClass("myspinner");
                                showLoading(false);
                                alert("authorized Successful.");
                            }
                            btn.html("<img style=\'float:left;\' src=\''.HCGS_URL.'/asset/images/bullet-green.png\'/> Authorized");
                            console.log("Successful.");
                        },
                        error: function() {
                            alert("Error to authorize to clickgumshoe server.");
                            if(++done==1) btn.on("click",adlock_authorize_event).removeClass("myspinner");//2
                        }
                    });
                    //also for opptuity to get new active servers when user re-authorize 
                    /*jQuery.ajax({
                        url: "'. HCGS_AJAX_URL .'?action=hcgs_rest&task=client_get_active_servers",
                        type: "post",
                        dataType: "json",
                        data: {site: location.hostname},
                        success: function(res) {
                            if(typeof res=="object" && res.data) {
                                res.data = res.data.data;
                                jQuery.ajax({
                                    url: "'.HCGS_AJAX_URL.'?action=hcgs_save_userdata&nonce='.$nonce.'",
                                    type: "POST",
                                    dataType: "json",
                                    data: {
                                        data: {
                                            data: res.data, time: + new Date()
                                        },
                                        cache_data:1, 
                                        cache_name: "active_servers",
                                        nonce: "'.$nonce.'"
                                    },
                                    success: function(dt) {
                                        if(++done==2) {
                                            btn.on("click",adlock_authorize_event).removeClass("myspinner");
                                            showLoading(false);
                                        }
                                        console.log("Done! save user data to cache.");
                                    },
                                    error: function() {
                                        alert("Error to authorize to clickgumshoe server.");
                                        if(++done==2) btn.on("click",adlock_authorize_event).removeClass("myspinner");
                                    }
                                });
                            }
                            else alert("Error data response from clickgumshoe server.");
                        }
                    });*/
                }, 
                error: function(e) {
                    console.log(e);
                    btn.on("click",adlock_authorize_event).removeClass("myspinner");
                }
            });
        };

        jQuery(document).ready(function(){
            var manager_url="'.rtrim(HCGS_MANAGER,'/').'";
            if(typeof $=="undefined") $=jQuery;
            var authorize_status = '.(empty($data)? '0':'1').';

            site_check(function(res){
                manager_url="https://clickgumshoe.com";
                if(res.exist) {
                    jQuery("#site_check").html("<img style=\'float:left;\' src=\''.HCGS_URL.'/asset/images/bullet-green.png\'/> Registered. Please <a href=\'"+manager_url+"/login\' target=\'_blank\'>login</a>.");
                }
                else {
                    jQuery("#site_check").html("<img style=\'float:left;\' src=\''.HCGS_URL.'/asset/images/bullet-black.png\'/> Please <a href=\'"+manager_url+"/signup\' target=\'_blank\'>register</a>.");
                }
                jQuery("#site_check").removeClass("myspinner");
            });
            
            jQuery("#btn_ad_authorize").click(adlock_authorize_event);
            jQuery("#btn_ad_authorize").html( (authorize_status? "<img style=\'float:left;\' src=\''.HCGS_URL.'/asset/images/bullet-green.png\'/> Re-authorize": "<img style=\'float:left;\' src=\''.HCGS_URL.'/asset/images/bullet-black.png\'/> authorize"));
            jQuery("a.test-clickgumshoe").click(function(){
                var ip=jQuery("input#ads_test_ip").val(), fakedev=jQuery("#random_devid").is(":checked"),url = jQuery(this).attr("href"),$l = $(this);
                url = url.replace(/&_test_ip\=[\d\.]+/g,"");
                if($l.hasClass("organic-click-link")) setCookie("complete_data","");
                //if(!ip && jQuery("input#ads_test_ip").length==0) ip= "'.$ip.'"? "'.$ip.'" : generateIP();
                if(ip) url+="&_test_ip="+ip;
                if(fakedev || jQuery("input#ads_test_ip").length==0) url+="&_fake_browser=1";
                jQuery(this).attr("href", url);
            });
            jQuery(".generate-test-ip").on("click", function(e){
                jQuery("input#ads_test_ip").val(generateIP());
            });
            jQuery("#cgs_clean_cache").on("click", function(e){
                var btn=jQuery(this);
                btn.addClass("myspinner");
                showLoading(1,{timeout:10000000000000});
                jQuery.ajax({
                    url: "'. HCGS_AJAX_URL .'?action=hcgs_lock_debug&test=clean_cache&nonce='.wp_create_nonce("hcgs_lock_debug").'",
                    type: "POST",
                    //dataType: "json",
                    data: {},
                    success: function(dt) {
                        var validate = function() {
                        jQuery.ajax({
                            url: "'. HCGS_AJAX_URL .'?action=hcgs_lock_debug&nonce='.wp_create_nonce("hcgs_lock_debug").'",
                            type: "GET",
                            dataType:"json",
                            success: function(r){
                                if(r && (!r.data["cache-active_servers"] || !r.data["active_servers"]) && i++<5) return validate();
                                else {
                                    btn.removeClass("myspinner");;
                                    showLoading(false);
                                }
                                console.log(r);
                                alert("Flush cache Successful.");
                            }
                        });}, i=0;
                        validate();
                        
                    },
                    error: function() {
                        console.error("Error to flush clickgumshoe cache.");
                    }
                });
            });
        });
        </script>
        <style>
        #admin-page-framework-form{position: relative;}
        </style>'; 
    } 
} 
HCGS_GeneralSettings::add_setting_tab(); 