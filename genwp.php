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

class GenWP {

    const VERSION = '1.0.0';

    public function __construct() {
        $this->includes();

        new genwp_Settings();
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once GENWP_PLUGIN_DIR . 'includes/class-openai.php';
        require_once GENWP_PLUGIN_DIR . 'admin/partials/article-settings.php';
        require_once GENWP_PLUGIN_DIR . 'admin/genwp-settings.php';
        require_once GENWP_PLUGIN_DIR . 'includes/class-writer.php';
        require_once GENWP_PLUGIN_DIR . 'includes/class-db.php';
        require_once GENWP_PLUGIN_DIR . 'includes/cron.php';
        require_once GENWP_PLUGIN_DIR . 'admin/partials/gen-admin.php';
        require_once GENWP_PLUGIN_DIR . 'admin/partials/keywords-table.php';
        require_once GENWP_PLUGIN_DIR . 'includes/class-image-generator.php';
        require_once GENWP_PLUGIN_DIR . 'includes/class-featured-image.php';
        require_once GENWP_PLUGIN_DIR . 'includes/keywords-uploader.php';

        require_once GENWP_PLUGIN_DIR . 'includes/genwp-activator.php';
        require_once GENWP_PLUGIN_DIR . 'includes/genwp-deactivator.php';

        require_once GENWP_PLUGIN_DIR . 'vendor/autoload.php';
    }
}

/**
 * Initialize the plugin.
 */
function init_genwp() {    
    register_activation_hook(__FILE__, 'GenWP\genwp_Activation::activate');
    register_deactivation_hook(__FILE__, 'GenWP\genwp_Deactivation::deactivate');

    new GenWP();
}

add_action('plugins_loaded', 'GenWP\init_genwp');