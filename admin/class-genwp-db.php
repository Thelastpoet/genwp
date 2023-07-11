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

    /**
     * Initializes the genWP_Db object.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'genwp';

        register_activation_hook( __FILE__, array( $this, 'create_table' ) );
        add_action( 'admin_init', array( $this, 'check_table' ) );
    }

    /**
     * Create the database table for storing keywords and titles.
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id INT NOT NULL AUTO_INCREMENT,
            keyword VARCHAR(255) NOT NULL,
            taxonomy_term VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Check if the database table exists, and create it if it doesn't.
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
     * @param string $title    The title associated with the keywords.
     */
    public function saveKeywords( $keywords, $title, $taxonomy_term ) {
        global $wpdb;
    
        foreach ( $keywords as $keyword ) {
            $wpdb->insert(
                $this->table_name,
                array(
                    'keyword' => $keyword,
                    'title'   => $title,
                    'taxonomy_term' => $taxonomy_term,
                ),
                array(
                    '%s',
                    '%s',
                    '%s'
                )
            );
        }
    }

    /**
     * Retrieve all keywords, taxonomy_terms, and titles from the database.
     *
     * @return array An array of keyword-title-taxonomy_term trios.
     */
    public function get_keywords() {
        global $wpdb;

        $query = "SELECT keyword, title, taxonomy_term FROM $this->table_name;";
        return $wpdb->get_results( $query, ARRAY_A );
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
}