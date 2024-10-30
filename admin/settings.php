<?php
include_once (HCGS_DIR. '/inc/admin-tabs.php'); 

class HCGS_Settings_page extends AdminPageFramework{ 
	const PAGE_SLUG = 'hcgs_settings'; 

	static public function valid_tab_slug($str){ 
		if(is_string($str)) $str = preg_replace('#[\s@\#\$\!%\^\&\*\(\)\-\+\[\]\=\~]#','_',$str); 
		return $str; 
	} 

	function get_tabs($tab='') { 
		return $tab? (isset(HCGS_Setting_Tab::$tabs[$tab])? HCGS_Setting_Tab::$tabs[$tab]: null) : HCGS_Setting_Tab::$tabs; 
	} 

	public function setUp() { 
		hcgs_load_tab('general'); 
		$this->setRootMenuPage( 'Settings'); 
		$this->addSubMenuItems( array( 'title' => 'ClickGUMSHOE', 'page_slug' => self::PAGE_SLUG ) ); 

		foreach($this->get_tabs() as $slug => $tab) { 
			$this->addInPageTabs(
				self::PAGE_SLUG, 
				array( 
					'tab_slug' => $slug, 
					'title' => $tab['title'], 
					'description' => $tab['description'] 
				)
			); 
			if(isset($tab['init']) && is_callable($tab['init']) ) { 
				call_user_func($tab['init'], $slug, $tab, $this); 
			} 
			elseif(isset ($tab['callback']) && is_callable($tab['callback']) ) { 
				add_action( 'load_' . self::PAGE_SLUG . '_' . self::valid_tab_slug($slug), $tab['callback'] ); 
			} 
			elseif( method_exists($this, 'replyToAddFormElements_tab_'.$slug )) { 
				add_action( 'load_' . self::PAGE_SLUG . '_' . self::valid_tab_slug($slug), array( $this, 'replyToAddFormElements_tab_'.$slug ) ); 
			} 
		} 
		$this->setInPageTabTag( 'h2' ); 
	} 

	public function do_hcgs_settings() { 
		$active_tab = isset($_GET['tab'])? $_GET['tab']: ''; 
		foreach($this->get_tabs() as $slug => $tab) { 
			if(!$active_tab || $active_tab==get_class($tab['instance'])) { 
				if(method_exists($tab['instance'], 'do_html')) $tab['instance']->do_html(); 
				break; 
			}; 
		} 
	} 

	public function validation_HCGS_Settings_page( $sInput, $sOldInput ) { 
		return $sInput; 
	} 

	public function validate( $aSubmit, $aStored, $oAdminWidget ) { #die;
		//clear cache;
		hcgs_deleteDir(HCGS_DIR.'/html/data/cache/');
		if(is_dir(WP_CONTENT_DIR.'/cache')) hcgs_deleteDir(WP_CONTENT_DIR.'/cache');

		return $aSubmit; 
	} 
} 
if(is_admin()) new HCGS_Settings_page();