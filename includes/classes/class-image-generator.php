<?php

namespace GenWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ImageGenerator {
    private $pexels_api_key;

    public function __construct() {
        // API Key
        $this->pexels_api_key = get_option('genwp_pexels_api_key');
    }

    public function pexels_generate_image( $keyword, $orientation = 'landscape', $size = 'large2x' ) {
        $url = 'https://api.pexels.com/v1/search?query=' . urlencode($keyword) . '&orientation=' . urlencode($orientation) . '&size=' . urlencode($size) . '&per_page=1';
        $args = array(
            'headers' => array(
                'Authorization' => $this->pexels_api_key
            )
        );
    
        $response = wp_remote_get($url, $args);
    
        if (is_wp_error($response)) {
            error_log('Pexels API request failed: ' . $response->get_error_message());
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    
        if (!empty($data->photos)) {
            return array(
                'image_url' => $data->photos[0]->src->large2x,
                'photographer' => $data->photos[0]->photographer,
                'photographer_url' => $data->photos[0]->photographer_url,
            );
        }
    
        return false;
    }    
}