<?php

namespace genwp;

class genwp_Cron {
    public function __construct() {
        add_filter('cron_schedules', array(__CLASS__, 'genwp_add_cron_interval'));

        $this->schedule_event();

        add_action('genwp_cron', [$this, 'cron_callback']);
        add_action('genwp_settings_updated', array($this, 'on_update_settings'), 10, 2);
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
        $image_generator = new ImageGenerator();
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
        $settings = get_option('genwp_article_settings', array());

        $frequency = isset($settings['genwp_cron_frequency']) ? $settings['genwp_cron_frequency'] : 5;
    
        $interval = round(24 * 60 * 60 / $frequency);
        $nearest_minute_interval = $interval - ($interval % 60); 
    
        $schedules['genwp_frequency'] = array(
            'interval' => $nearest_minute_interval, 
            'display' => sprintf(__('%d times daily'), $frequency)
        );
    
        return $schedules;
    }

    public function on_update_settings($old_value, $new_value) {
        // If the frequency setting has changed, reschedule the event
        if (isset($old_value['genwp_cron_frequency']) && isset($new_value['genwp_cron_frequency']) && $old_value['genwp_cron_frequency'] !== $new_value['genwp_cron_frequency']) {
            wp_clear_scheduled_hook('genwp_cron');
            $this->schedule_event();
        }
    }

    public function schedule_event() {
        // Get the current frequency
        $settings = get_option('genwp_article_settings', array());
        $frequency = isset($settings['genwp_cron_frequency']) ? $settings['genwp_cron_frequency'] : 5;
    
        // Calculate the interval and the time for the next event
        $interval = round(24 * 60 * 60 / $frequency);
        $next_event_time = time() + $interval;
    
        // Clear any existing event and schedule a new one at the calculated time
        wp_clear_scheduled_hook('genwp_cron');
        wp_schedule_event($next_event_time, 'genwp_frequency', 'genwp_cron');
    }
    
}
