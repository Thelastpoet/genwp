<?php
/**
 * The genWP_Db class handles the database operations for the genwp plugin.
 *
 * @package genwp
 */

namespace genwp;

if ( !defined( 'ABSPATH' )) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the database operations for the genwp plugin.
 */
class genWP_Db {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'genwp';

        register_activation_hook( __FILE__, array( $this, 'create_table' ) );
        add_action( 'admin_init', array( $this, 'check_table' ) );
    }

    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id INT NOT NULL AUTO_INCREMENT,
            keyword VARCHAR(255) NOT NULL,
            user_id INT,
            term_id INT,
            taxonomy_term VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Create db if it doesn't exist.
     */
    public function check_table() {
        if ( ! $this->is_table_exists() ) {
            $this->create_table();
        }
    }

    /**
     * Check if the database table exists.
     *
     * @return bool True if the table exists, false otherwise.
     */
    public function is_table_exists() {
        global $wpdb;
        return $wpdb->get_var( "SHOW TABLES LIKE '$this->table_name'" ) === $this->table_name;
    }

    /**
     * Save keywords and title to the database.
     *
     * @param array  $keywords An array of keywords.
     * @param string $title The title associated with the keywords.
     */
    public function saveKeywords( $keywords, $title = "", $taxonomy_term = "", $user_id = NULL, $term_id = NULL ) {
        global $wpdb;
    
        foreach ( $keywords as $keyword ) {
            $data = array(
                'keyword' => $keyword,
                'title'   => $title,
                'taxonomy_term' => $taxonomy_term,
            );
            $data_format = array('%s', '%s', '%s');
    
            if ($user_id !== NULL) {
                $data['user_id'] = $user_id;
                $data_format[] = '%d';
            }
            if ($term_id !== NULL) {
                $data['term_id'] = $term_id;
                $data_format[] = '%d';
            }
            
            $wpdb->insert($this->table_name, $data, $data_format);
        }
    }    

    /**
     * Retrieve all keywords, taxonomy_terms, and titles from the database.
     *
     * @return array An array of keyword-title-taxonomy_term trios.
     */
    public function get_keywords() {
        global $wpdb;
    
        $query = "SELECT id, keyword, title, user_id, term_id FROM $this->table_name;";
        return $wpdb->get_results($query, ARRAY_A);
    }   

    /**
     * Delete keywords from the database.
     *
     * @param array $keywords An array of keywords to be deleted.
     */
    public function delete_keywords( $keywords ) {
        global $wpdb;

        foreach ( $keywords as $keyword ) {
            $wpdb->delete(
                $this->table_name,
                array( 'keyword' => $keyword ),
                array( '%s' )
            );
        }
    }

    /**
     * Update keyword in the database.
     *
     * @param string $old_keyword The old keyword to be updated.
     * @param string $new_keyword The new keyword to replace the old one.
     * @return int|false The number of rows updated, or false on error.
     */
    public function update_keyword($old_keyword, $new_keyword) {
        global $wpdb;
    
        if (!empty($new_keyword)) {
            return $wpdb->update( 
                $this->table_name, 
                array( 'keyword' => sanitize_text_field($new_keyword) ), 
                array( 'keyword' => sanitize_text_field($old_keyword) ), 
                array( '%s' ), 
                array( '%s' ) 
            );
        }
    
        return false;
    }    
    
    /**
     * Update keyword mapping in the database.
     *
     * @param string $keyword The keyword whose mapping is to be updated.
     * @param int $userId The ID of the user to be mapped with the keyword.
     * @param int $termId The ID of the term to be mapped with the keyword.
     * @return int|false The number of rows updated, or false on error.
     */
    public function update_keyword_mapping($keyword, $user_id, $term_id) {
        global $wpdb;
        
        $data = array();
        $data_format = array();
        
        if (!empty($user_id)) {
            $data['user_id'] = $user_id;
            $data_format[] = '%d';
        }
        
        if (!empty($term_id)) {
            $data['term_id'] = $term_id;
            $data_format[] = '%d';
        }
    
        if (!empty($data)) {
            return $wpdb->update(
                $this->table_name,
                $data,
                array('keyword' => sanitize_text_field($keyword)),
                $data_format,
                array('%s')
            );
        }
    
        return false;
    }    
}