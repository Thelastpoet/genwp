<?php

namespace genwp;

class genwp_Cron {
    public function __construct() {
        add_filter('cron_schedules', array(__CLASS__, 'genwp_add_cron_interval'));

        if (!wp_next_scheduled('genwp_cron')) {
            wp_schedule_event(time(), 'five_times_a_day', 'genwp_cron');
        }
        add_action('genwp_cron', [$this, 'cron_callback']);
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
        $openverse_generator = new OpenverseImageGenerator('images');
		$featured_image = new FeaturedImage($openverse_generator);
        $writer = new genwp_Writer($ai_generator, $genwpdb, $featured_image, $openverse_generator);
        
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
        $schedules['five_times_a_day'] = array(
            'interval' => 5 * 60, // Interval to run 5 times a day
            'display' => __('Five times a day')
        );
    
        return $schedules;
    }
}
