<?php
/**
 * This is the admin menu page
 */

namespace GenWP;

use GenWP\genWP_Db;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class genwp_Settings {
    private $option_name = 'genwp_settings';
    private $page_title = 'GenWP Settings';
    private $menu_title = 'GenWP Writer';
    private $capability = 'manage_options';
    private $menu_slug = 'genwp-settings';
    private $icon_url = 'dashicons-welcome-write-blog';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_options_page'));   
    }
    
    public function add_options_page() {
        add_menu_page($this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this, 'display_settings_page'), $this->icon_url);
    }

   public function display_settings_page() {
    echo '<div class="wrap"><div id="genwp-admin"></div></div>';
   }
    
}