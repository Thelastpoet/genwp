<?php

namespace GenWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use GenWP\Gen_Key_Table;
use GenWP\genWP_Db;
use GenWP\KeywordsUploader;

class GenWP_Rest_Route {
    private $gen_key_table;
    private $keywords_uploader;
    private $genwpdb;
    
    public function __construct() {
        $this->gen_key_table = new Gen_Key_Table();
        $this->genwpdb = new genWP_Db();
        $this->keywords_uploader = new KeywordsUploader($this->genwpdb);
        add_action( 'rest_api_init', [ $this, 'genwp_register_routes' ] );
    }

    public function genwp_register_routes() {
        register_rest_route( 'genwp/v1', '/settings', [
            'methods' => 'POST',
            'callback' => [ $this, 'save_settings' ],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route( 'genwp/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [ $this, 'get_settings' ],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route('genwp/v1', '/authors', [
            'methods' => 'GET',
            'callback' => [$this, 'get_authors'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route('genwp/v1', '/types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_types'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route('genwp/v1', '/statuses', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_statuses'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route('genwp/v1', '/article-settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_article_settings'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route('genwp/v1', '/article-settings', [
            'methods' => 'POST',
            'callback' => [$this, 'save_article_settings'],
            'permission_callback' => [$this, 'permissions_check'],
        ]); 
        
        register_rest_route('genwp/v1', '/keywords', [
            'methods' => 'GET',
            'callback' => [$this->gen_key_table, 'get_keywords_data'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route('genwp/v1', '/keywords/mapping', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'update_keyword_mapping'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route('genwp/v1', '/keywords/update', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'update_keyword'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route('genwp/v1', '/keywords/delete', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'delete_keywords'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);
        
        register_rest_route('genwp/v1', '/keywords/write-articles', [
            'methods' => 'POST',
            'callback' => [$this->gen_key_table, 'write_articles'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        // Register the new taxonomy terms endpoint
        register_rest_route('genwp/v1', '/taxonomy-terms/(?P<taxonomy>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_taxonomy_terms'],
            'permission_callback' => [$this, 'permissions_check'],
        ]);

        register_rest_route('genwp/v1', '/upload-keywords', [
            'methods' => 'POST',
            'callback' => [ $this->keywords_uploader, 'upload_keywords' ],
            'permission_callback' => [$this, 'permissions_check'],
        ]);        
    }

    public function save_settings( \WP_REST_Request $request ) {
        // Perform nonce verification
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new \WP_Error('invalid_nonce', 'Invalid nonce.', ['status' => 401]);
        }

        $settings = $request->get_json_params();

        // Save the settings to the database
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'max_tokens':
                    $value = intval($value);
                    break;
                case 'temperature':
                    $value = floatval($value);
                    break;
                default:
                    $value = sanitize_text_field($value);
                    break;
            }
            update_option($key, $value);
        }

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
        $settings_keys = [
            'genwp-openai-api-key',
            'model',
            'max_tokens',
            'temperature',
            'top_p',
            'frequency_penalty',
            'presence_penalty',
            'pexels_api_key'

        ];

        $settings = [];
        foreach ($settings_keys as $key) {
            $settings[$key] = get_option($key);
        }

        return rest_ensure_response($settings);
    }

    public function permissions_check() {
      // return current_user_can('manage_options'); 
      return true;      
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
        $settings = get_option('genwp_article_settings', []);
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
        update_option('genwp_article_settings', $settings);
    
        return rest_ensure_response(['success' => true, 'message' => 'Settings saved successfully!']);
    }
    
}