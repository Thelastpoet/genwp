<?php

namespace GenWP;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include the genwp-db.php file
require_once GENWP_PLUGIN_DIR . 'includes/classes/genwp-db.php';
require_once GENWP_PLUGIN_DIR . 'includes/classes/cron.php';

class genwp_Activation {
    public static function activate() {
        // Create the database table
        $genwpdb = new genWP_Db();
        $genwpdb->create_table();

        // Schedule the cron job
        $cron = new genwp_Cron();
    }
}
