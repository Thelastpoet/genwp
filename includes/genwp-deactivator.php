<?php

namespace GenWP;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class genwp_Deactivation {
    public static function deactivate() {
        // Unschedule cron
        if (wp_next_scheduled('genwp_cron')) {
            wp_clear_scheduled_hook('genwp_cron');
        }
    }
}