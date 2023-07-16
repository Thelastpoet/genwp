<?php

namespace genwp;

class GenWP_Dir {
    private $upload_dir;

    public function __construct() {
        // Setup the directory
        add_action('init', array($this, 'genwp_dir'));
    }

    public function genwp_dir() {
        // Get WP's upload directory
        $wp_upload_dir = wp_upload_dir();

        // Define the path to your custom directory
        $this->upload_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'genwp';

        // Create the custom directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }

    public function get_upload_dir() {
        return $this->upload_dir;
    }
}

$new_genwp_dir = new GenWP_Dir();
