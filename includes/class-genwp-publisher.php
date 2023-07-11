<?php

namespace genwp;

use genwp\genwp_Drafts;

if ( !defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class genwp_Publisher {
    private $drafts;

    public function __construct(genwp_Drafts $drafts) {
        $this->drafts = $drafts;
    }

    public function publish_post(int $post_id) {
        $post_data = array(
            'ID' => $post_id,
            'post_status' => 'publish',

        );

        // Update the post status
        wp_update_post($post_data);        
    }
}