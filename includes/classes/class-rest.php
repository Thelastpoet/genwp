<?php

namespace GenWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use GenWP\Gen_Key_Table;
use GenWP\genWP_Db;
use GenWP\KeywordsUploader;

class GenWP_Rest extends \WP_REST_Controller {
    const ROUTE_NAMESPACE = 'genwp/v1';
    const SETTINGS_OPTION_NAME = 'genwp_settings';
    const ARTICLE_SETTINGS_OPTION_NAME = 'genwp_article_settings';
    const ARTICLE_SETTINGS_ROUTE = '/article-settings';
    const SETTINGS_ROUTE = '/settings';
    const KEYWORDS_ROUTE = '/keywords';
    const AUTHORS_ROUTE = '/authors';

    private $gen_key_table;
    private $keywords_uploader;
    private $genwpdb;
    
    public function __construct() {
        $this->gen_key_table = new Gen_Key_Table();
        $this->genwpdb = new genWP_Db();
        $this->keywords_uploader = new KeywordsUploader($this->genwpdb);
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( self::ROUTE_NAMESPACE, self::SETTINGS_ROUTE, [
            'methods' => 'POST',
            'callback' => [ $this, 'save_settings' ],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route( self::ROUTE_NAMESPACE, self::SETTINGS_ROUTE, [
            'methods' => 'GET',
            'callback' => [ $this, 'get_settings' ],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, self::AUTHORS_ROUTE, [
            'methods' => 'GET',
            'callback' => [$this, 'get_authors'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_types'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/statuses', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_statuses'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/article-settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_article_settings'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route(self::ROUTE_NAMESPACE, '/article-settings', [
            'methods' => 'POST',
            'callback' => [$this, 'save_article_settings'],
            'permission_callback' => [$this, 'permissions_check'],
        ]); 
        
        register_rest_route(self::ROUTE_NAMESPACE, '/keywords', [
            'methods' => 'GET',
            'callback' => [$this->gen_key_table, 'get_keywords_data'],
            'permission_callback' => [$this, 'permissions_check'],
            'args' => [
                'page' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'default' => 1,
                ],
                'limit' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'default' => 10,
                ]
            ],
        ]);
        
        
        register_rest_route(self::ROUTE_NAMESPACE, '/keywords/mapping', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'update_keyword_mapping'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/keywords/update', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'update_keyword'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route(self::ROUTE_NAMESPACE, '/keywords/delete', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'delete_keywords'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route(self::ROUTE_NAMESPACE, '/keywords/write-articles', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'write_articles'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // Register the new taxonomy terms endpoint
        register_rest_route(self::ROUTE_NAMESPACE, '/taxonomy-terms/(?P<taxonomy>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_taxonomy_terms'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/upload-keywords', [
            'methods' => 'POST',
            'callback' => [ $this->keywords_uploader, 'upload_keywords' ],
            'permission_callback' => [$this, 'permissions_check'],
        ]); 
        
        register_rest_route(self::ROUTE_NAMESPACE, '/get-(?P<keyType>.+)-api-key', [
            'methods' => 'GET',
            'callback' => [$this, 'get_api_key'],
            'permission_callback' => [$this, 'permissions_check'],
            'args' => [
                'key' => [
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ]
            ]
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/(?P<keyType>.+)-api-key', [
            'methods' => 'POST',
            'callback' => [$this, 'save_api_key'],
            'permission_callback' => [$this, 'permissions_check'],
            'args' => [
                'key' => [
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ]
            ]
        ]);
    }

    public function save_api_key(\WP_REST_Request $request) {
        $keyType = $request->get_param('keyType');
        $key = $request->get_param('key');

        $result = update_option("genwp_{$keyType}_api_key", $key);

        if ($result) {
            return rest_ensure_response(['success' => true, 'message' => 'API Key saved successfully!']);
        } else {
            return rest_ensure_response(['success' => false, 'message' => 'Failed to save the API key.']);
        }
        
    }

    public function get_api_key(\WP_REST_Request $request) {
        $keyType = $request->get_param('keyType');

        $api_key = get_option("genwp_{$keyType}_api_key");

        if ($api_key) {
            return rest_ensure_response(['success' => true, 'key' => $api_key]);
        } else {
            return rest_ensure_response(['success' => false, 'message' => 'API Key not set for this type.']);
        }
        
    }

    public function save_settings( \WP_REST_Request $request ) {
        // Perform nonce verification
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Invalid nonce.', ['status' => 401]);
        }

        $settings = $request->get_json_params();

        // Save the settings to the database
        update_option('genwp_settings', $settings);

        return rest_ensure_response(['success' => true, 'message' => 'Settings saved successfully!']);
    }

    public function get_taxonomy_terms(\WP_REST_Request $request) {
        $taxonomy = $request->get_param('taxonomy');
    
        if (empty($taxonomy)) {
            return new \WP_Error('missing_taxonomy', 'Taxonomy parameter is required.', ['status' => 400]);
        }
    
        // Get the terms for the specified taxonomy
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);
    
        // Return the terms
        return rest_ensure_response($terms);
    }

    public function get_settings() {
        $settings = get_option('genwp_settings', []);

        return rest_ensure_response($settings);
    }

    public function permissions_check() {
      return current_user_can('manage_options');    
    }

    public function get_authors() {
        $args = array(
            'role__in' => array('Administrator', 'Editor', 'Author', 'Contributor')
        );
        $users = get_users($args);
        $authors = [];

        foreach ($users as $user) {
            $authors[] = ['id' => $user->ID, 'name' => $user->display_name];
        }

        return rest_ensure_response($authors);
    }

    public function get_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        $types = [];

        foreach ($post_types as $post_type) {
            $types[] = $post_type->name;
        }

        return rest_ensure_response($types);
    }

    public function get_post_statuses() {
        $statuses = get_post_stati();
        return rest_ensure_response(array_keys($statuses));
    }  

    public function get_article_settings() {
        $settings = get_option(self::ARTICLE_SETTINGS_OPTION_NAME, []);
        return rest_ensure_response($settings);
    }
    

    public function save_article_settings(\WP_REST_Request $request) {
        // Perform nonce verification
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Invalid nonce.', ['status' => 401]);
        }
    
        $settings = $request->get_json_params();
       
       // Save the settings
       update_option(self::ARTICLE_SETTINGS_OPTION_NAME, $settings);
    
        return rest_ensure_response(['success' => true, 'message' => 'Settings saved successfully!']);
    }
    
}