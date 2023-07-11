<?php

namespace genwp;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Gen_Key_Table extends \WP_List_Table {
    private $keywords = array();

    public function __construct($keywords) {
        parent::__construct(array(
            'singular' => 'keyword',
            'plural' => 'keywords',
            'ajax' => false
        ));
        $this->keywords = $keywords;

        add_action( 'admin_notices', 'genwp_show_admin_notices' );
    }

    public function prepare_items() {
        $db = new \genwp\genWP_Db();
        $genwpCron = new \genwp\genwp_Cron();
    
        $action = $this->current_action();
    
        // verify the nonce field
        if (isset($_REQUEST['keywords']) && is_array($_REQUEST['keywords']) && !wp_verify_nonce($_POST['bulk-delete-nonce'], 'bulk-delete')) {
            die('Invalid request');
        }

        if ($action === 'delete') {
            $db->delete_keywords($_REQUEST['keywords']);
        }

        if ($action === 'write') {
            // Store the selected keywords as a WordPress option
            update_option('genwp_selected_keywords', $_REQUEST['keywords']);

            // Set a transient to show the success message
            set_transient( 'genwp_write_success', true, 5 );
        }            

        // Refresh keywords after deletion
        $this->keywords = $db->get_keywords();
    
        $columns = $this->get_columns();
        $this->_column_headers = array($columns, array(), array());
        
        // Define the number of items per page
        $per_page = 20;
        
        $current_page = $this->get_pagenum();
        
        // Ensure the keywords array is sliced according to the current page and items per page.
        $current_page_index = ($current_page-1) * $per_page;
        $this->items = array_slice($this->keywords, $current_page_index, $per_page);
    
        // Setting up pagination
        $this->set_pagination_args(array(
            'total_items' => count($this->keywords),
            'per_page'    => $per_page
        ));
    }    

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />', // To add a checkbox
            'keyword' => 'Keyword',
            'actions' => 'Actions'
        );
    }

    public function column_cb($item) {
        // Add a "Select All" checkbox to the table header
        static $print_header = true;
        if ($print_header) {
            $print_header = false;
            return '<input type="checkbox" onclick="jQuery(\'input[name*=\\\'keywords\\\']\').attr(\'checked\', this.checked);" />';
        }
        
        // Add a checkbox for each keyword in the table
        return sprintf(
            '<input type="checkbox" name="keywords[]" value="%s" />', $item['keyword']
        );    
    }    
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'keyword':
                return $item['keyword'];  // Assuming 'keyword' is a key in $item
            case 'actions':
                // Display an edit button
                return sprintf('<a href="?page=%s&action=%s&keyword=%s">Edit</a>', 'edit_keyword', 'edit', $item['keyword']); // Assuming 'keyword' is a key in $item
            default:
                return print_r($item, true);
        }
    }   

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
            'write'    => 'Write'
        );
        return $actions;
    }

    function genwp_show_admin_notices() {
        if ( get_transient( 'genwp_write_success' ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Successfully started writing the articles.', 'genwp' ); ?></p>
            </div>
            <?php
            delete_transient( 'genwp_write_success' );
        }
    }
}