<?php

namespace genwp;

use \WP_Error;

class OpenverseImageGenerator {
    private $api_base_url = 'https://api.openverse.engineering/v1/';
    private $endpoint = 'images';
    private $license = 'cc0,by,by-sa,by-nc,by-nd,by-nc-sa,by-nc-nd';
    private $extension = 'jpeg';
    private $aspect_ratio = 'wide';
    private $size = 'large';

    public function __construct($endpoint = 'images') {
        $this->endpoint = $endpoint;
    }

    public function get_image($keyword) {
        $query = http_build_query([
            'q' => $keyword,
            'license' => $this->license,
            'extension' => $this->extension,
            'aspect_ratio' => $this->aspect_ratio,
            'size' => $this->size,
            'per_page' => 1
        ]);

        $response = wp_remote_get($this->api_base_url . $this->endpoint . '?' . $query);

        if (is_wp_error($response)) {
            return new WP_Error('openverse_api_error', 'Error retrieving image from Openverse API: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (!isset($data->results) || empty($data->results)) {
            return new WP_Error('openverse_api_error', 'No images found for keyword: ' . $keyword);
        }

        return $data->results[0]->url;
    }
}