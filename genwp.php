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

namespace GenWP;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants for plugin directory and URL.
define('GENWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GENWP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the activation and deactivation classes.
require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-activator.php';
require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-deactivator.php';

class GenWP {

    const VERSION = '1.0.0';

    public function __construct() {
        $this->includes();
        new genWP_Core;
        new genWP_Settings;
        new GenWP_Rest_Route();
        new genwp_Cron();
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once GENWP_PLUGIN_DIR . 'genwp-core.php';
        require_once GENWP_PLUGIN_DIR . 'includes/admin/genwp-settings.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/class-genwp-routes.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/keywords-table.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-db.php';
        
        require_once GENWP_PLUGIN_DIR . 'includes/classes/class-openai.php';
        
    
        require_once GENWP_PLUGIN_DIR . 'includes/classes/class-writer.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/cron.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/class-image-generator.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/class-featured-image.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/keywords-uploader.php';

        require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-activator.php';
        require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-deactivator.php';

        require_once GENWP_PLUGIN_DIR . 'vendor/autoload.php';
    }
}

/**
 * Initialize the plugin.
 */
function init_genwp() {  
    new GenWP();
}

/**
 * Activation hook callback.
 */
function activate_genwp() {
    require_once GENWP_PLUGIN_DIR . 'includes/classes/cron.php';
    require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-db.php';
    require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-activator.php';
    genwp_Activation::activate();
}

/**
 * Deactivation hook callback.
 */
function deactivate_genwp() {
    require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-deactivator.php';
    genwp_Deactivation::deactivate();
}

add_action('plugins_loaded', 'GenWP\init_genwp');
register_activation_hook(__FILE__, 'GenWP\activate_genwp');
register_deactivation_hook(__FILE__, 'GenWP\deactivate_genwp');