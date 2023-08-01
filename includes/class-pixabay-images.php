<?php
namespace genwp;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PixabayImageGenerator {
    private $api_key;

    public function __construct() {
        // API Key
        $settings = get_option('genwp_settings', []);
        $this->api_key = isset($settings['genwp-pexels-api-key']) ? $settings['genwp-pexels-api-key'] : '';
    }

    public function generate_image($keyword, $orientation = 'landscape') {
        $url = 'https://pixabay.com/api/?key=' . $this->api_key . '&q=' . urlencode($keyword) . '&orientation=' . urlencode($orientation) . '&image_type=photo';
        $args = array();

        $response = wp_remote_get($url, $args);
        error_log(print_r($response, true));

        if (is_wp_error($response)) {
            error_log('Pixabay API request failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        error_log(print_r($body, true));

        if (isset($data->hits) && !empty($data->hits)) {
            return array(
                'image_url' => $data->hits[0]->webformatURL,
                'photographer' => $data->hits[0]->user,
                'photographer_url' => 'https://pixabay.com/users/' . $data->hits[0]->user_id, 
            );
        }

        return false;
    }
}