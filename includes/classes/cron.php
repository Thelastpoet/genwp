<?php

namespace GenWP;

class genwp_Cron {
    public function __construct() {
        add_filter('cron_schedules', array(__CLASS__, 'genwp_add_cron_interval'));
        $article_settings = get_option('genwp_article_settings', array());
        $cron_frequency = isset($article_settings['genwp_cron_frequency']) ? (int) $article_settings['genwp_cron_frequency'] : 5;
        $cron_interval = 'genwp_cron_' . $cron_frequency . '_times_a_day';

        if (!wp_next_scheduled('genwp_cron')) {
            wp_schedule_event(time(), $cron_interval, 'genwp_cron');
        }
        add_action('genwp_cron', [$this, 'cron_callback']);
        add_action('update_option_genwp_article_settings', array($this, 'reschedule_event'));
    }

    public function cron_callback() {
        // Check if there is an already running cron job
        $genwp_cron_running = get_option('genwp_cron_running', false);

        if ($genwp_cron_running != false) {
            // Check if the cron is running for more than 5 minutes
            if (time() - $genwp_cron_running > 300) {
                // More than 5 minutes, delete the option
                delete_option('genwp_cron_running');
            } else {
                // Less than 5 minutes, exit
                return;
            }
        }

        // Set that a cron is running and set the value to the time it is running on 
        update_option('genwp_cron_running', time());

        // Instantiate the OpenAIGenerator, genWP_Db, and genwp_Writer classes
        $ai_generator = new OpenAIGenerator();
        $genwpdb = new genWP_Db();
        $image_generator = new ImageGenerator;
		$featured_image = new FeaturedImage($image_generator);
        $writer = new genwp_Writer($ai_generator, $genwpdb, $featured_image, $image_generator);
        
        // Get the selected keywords from the WordPress options
        $keywords = get_option('genwp_selected_keywords', []);
        
        // Generate an article for the first keyword in the list
        if (!empty($keywords)) {
            $keyword = array_shift($keywords);
            $writer->gen_article($keyword);

            // Update the selected keywords option
            update_option('genwp_selected_keywords', $keywords);
        }

        // Delete the option flag that the cron job is running
        delete_option('genwp_cron_running');
    }

    // Add custom cron schedule
    public static function genwp_add_cron_interval($schedules) {
        $article_settings = get_option('genwp_article_settings', array());
        $cron_frequency = isset($article_settings['genwp_cron_frequency']) ? (int) $article_settings['genwp_cron_frequency'] : 5;

        $schedules['genwp_cron_' . $cron_frequency . '_times_a_day'] = array(
            'interval' => ($cron_frequency != 0) ? (86400 / $cron_frequency) : (86400 / 5),
            'display' => sprintf(__('%d times a day', 'genwp'), $cron_frequency)
        );

        return $schedules;
    }

    public function reschedule_event() {
        $article_settings = get_option('genwp_article_settings', array());
        $cron_frequency = isset($article_settings['genwp_cron_frequency']) ? (int) $article_settings['genwp_cron_frequency'] : 5;
        $cron_interval = 'genwp_cron_' . $cron_frequency . '_times_a_day';

        // Unschedule the existing event
        wp_clear_scheduled_hook('genwp_cron');

        // Schedule the new event with the updated frequency
        if (!wp_next_scheduled('genwp_cron')) {
            wp_schedule_event(time(), $cron_interval, 'genwp_cron');
        }
    }

}