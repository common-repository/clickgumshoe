<?php
/**
 * Interface HCGS_Tab_Interface
 */
interface HCGS_Tab_Interface {
    /**
     * @return array
     */
    public static function tab_info() ;

    /**
     * @return mixed
     */
    public function get_field_definition();

    /**
     * @return mixed
     */
    public function get_fields_definition();
    public function do_html();
}
/**
 * Class HCGS_Setting_Tab
 */
abstract class HCGS_Setting_Tab implements HCGS_Tab_Interface{
    public static $setting;
    public static $setting_tab;
    /**
     * @var array
     */
    public static $tabs = array();

    /**
     * return given tab object
     * @param string $tab
     * @return mixed
     */
    public static function get_tab($tab='') {
        if(!$tab) $tab = get_called_class();
        if(isset(self::$tabs[$tab])) return self::$tabs[$tab];
    }

    /**
     * @param $option
     * @param string $default
     * @return mixed|null|void
     */
    public static function get_options($option='', $default='') {
        $child = get_called_class();
        if($option) $option = array($child::SETTINGS_GROUP, $option);
        else $option = $child::SETTINGS_GROUP;
        return hcgs_option($option, $default) ;
    }
    /**
     * register setting tab
     */
    final static function add_setting_tab() {
        $child = get_called_class() ;
        if(isset(self::$tabs[$child])) return ;

        self::$tabs[$child] = call_user_func(array($child, 'tab_info'));
        if(method_exists($child, 'get_instance')) {
            self::$tabs[$child] ['instance'] = $child::get_instance();
        }
        else self::$tabs[$child] ['instance'] = new $child;

        if(!isset (self::$tabs[$child]['init'])
            && method_exists($child, 'init') ) {
            self::$tabs[$child]['init'] = array($child, 'init');    //init callback
        }
    }

    /**
     * get singleton of class
     * @return mixed
     */
    final static function get_instance() {
        $child = get_called_class() ;
        if(!$child::$instance) $child::$instance = new $child;
        return $child::$instance ;
    }
    /**
     * @param $slug
     * @param $tab
     * @param $setting
     */
    final public static function init($slug, $tab, $setting) {
        $tab['slug'] = $slug;
        self::$setting = $setting ;
        self::$setting_tab = $tab ;

        add_action('load_' . $setting::PAGE_SLUG . '_' . $setting::valid_tab_slug($slug), get_called_class().'::replyToAddFormElements');
    }
    /**
     * register APF field
     * @param AdminPageFramework $apf
     * @param bool $enabled_submit
     * @param bool $toggle_field
     */
    final static public function register_fields(AdminPageFramework &$apf, $enabled_submit= true, $toggle_field=true) {
        $class = get_called_class();    //refer to child class that extend this class
        $fields = array();      //fields setting
        $fields[] = $class::get_instance()->get_field_definition();
        //add more fields
        if(method_exists($class::get_instance(), 'get_fields_definition')) {
            $_fields = $class::get_instance()->get_fields_definition();
            if(is_array($_fields)) $fields = array_merge($fields, $_fields);
        }
        if($toggle_field) $apf->addSettingField(
            array(
                'field_id' => 'enable',
                'type' => 'checkbox',
                'label' => 'Kích hoạt'
            )
        );
        //register field
        foreach($fields as $field) {
            if(is_array($field)) $apf->addSettingField($field);
        }
        if($enabled_submit) {
            //add submit button
            $apf->addSettingField(array(
                'field_id' => 'submit',
                'type' => 'submit',
                'label' => 'Lưu lại',
                'show_title_column' => false,     #hidden button title column mean align button to left bellow field label, see image in bellow:
            ));
        }
    }
    public function get_field_definition(){}
    public function get_fields_definition(){}
    public function do_html(){}
}