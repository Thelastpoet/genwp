<?php

/**
 * Plugin Name: GenWP
 * Plugin URI: https://nabaleka.com
 * Description: GenWP creates full WordPress posts with OpenAI Models.
 * Version: 1.0.0
 * Author: Ammanulah Emmanuel
 * Author URI: https://ammanulah.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: genwp
 * Domain Path: /languages
 */

 namespace genwp;

 if (!defined('ABSPATH')) {
     exit; // Exit if accessed directly.
 }

 class Genwp {
 
    const VERSION = '1.0.0';
    public $PLUGIN_DIR;
    public $PLUGIN_URL;
 
    public function __construct() {
        $this->PLUGIN_DIR = plugin_dir_path(__FILE__);
        $this->PLUGIN_URL = plugin_dir_url(__FILE__);
 
        $this->includes();
 
        new genwp_Settings();
        new genwp_Cron();

        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }
 
    private function includes() {
        require_once $this->PLUGIN_DIR . 'includes/class-genwp-openai.php';
        require_once $this->PLUGIN_DIR . 'admin/class-genwp-settings.php';
        require_once $this->PLUGIN_DIR . 'includes/class-genwp-writer.php';
        require_once $this->PLUGIN_DIR . 'includes/class-genwp-db.php';
        require_once $this->PLUGIN_DIR . 'includes/cron.php';
        require_once $this->PLUGIN_DIR . 'admin/partials/gen-admin.php';
        require_once $this->PLUGIN_DIR . 'admin/partials/gen-key-table.php';
        require_once $this->PLUGIN_DIR . 'includes/class-genwp-dir.php';

        require_once $this->PLUGIN_DIR . 'vendor/autoload.php';
    }

    public function activate_plugin() {
        $genwp_db = new genWP_Db();
        $genwp_db->create_table();
    }
}
new genwp();