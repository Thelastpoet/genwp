<?php

namespace GenWP;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class genwp_Activation {
    public static function activate() {
        // Create the database table
        $genwpdb = new genWP_Db();
        $genwpdb->create_table();

        // Schedule the cron job
        $cron = new genwp_Cron();
    }
}