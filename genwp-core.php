<?php

namespace GenWP;

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class genWP_Core {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'genwp_enqueue_scripts'));
    }

    public function genwp_enqueue_scripts($hook) {
        // Check if we're on the genwp-settings page
        if ('toplevel_page_genwp-settings' !== $hook) {
            return;
        }

        wp_enqueue_script('genwp-settings', GENWP_PLUGIN_URL . 'build/index.js', array('jquery', 'wp-element'), GenWP::VERSION, true);
        wp_enqueue_style('genwp-styles', GENWP_PLUGIN_URL . 'build/index.css', array(), GenWP::VERSION);

        wp_localize_script('genwp-settings', 'genwpLocal', [
            'apiURL' => home_url('/wp-json'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }
}