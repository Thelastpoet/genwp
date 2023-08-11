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

        $settings_keys = [
            'genwp-openai-api-key',
            'model',
            'max_tokens',
            'temperature',
            'top_p',
            'frequency_penalty',
            'presence_penalty',
            'genwp_default_author',
            'genwp_default_post_type',
            'genwp_default_post_status',
            'genwp_cron_frequency'            
        ];

        foreach ($settings_keys as $key) {
            delete_option($key);
        }
    }
}