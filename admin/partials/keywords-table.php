<?php

namespace GenWP;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Gen_Key_Table extends \WP_List_Table {
    private $keywords = array();
    private $db;
    private $users;
    private $categories;

    public function __construct($keywords, \GenWP\genWP_Db $db) {
        parent::__construct(array(
            'singular' => 'keyword',
            'plural' => 'keywords',
            'ajax' => false
        ));

        $this->db = $db;
        $this->process_bulk_action();
        $this->keywords = $keywords;
        $this->users = get_users();
        $this->categories = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
    }

    // Helper function to generate a dropdown - -We will move this to functions file
    private function generate_dropdown($items, $selected_value, $class, $value_field, $label_field, $keyword) {
        $options = '';
        foreach ($items as $item) {
            $selected = $item->{$value_field} == $selected_value ? 'selected' : '';
            $options .= sprintf('<option value="%s" %s>%s</option>', $item->{$value_field}, $selected, $item->{$label_field});
        }
        return sprintf('<select name="%s[%s]" class="%s">%s</select>', $class, sanitize_text_field($keyword), $class, $options);
    }    

    public function prepare_items() {    
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
            'cb' => '<input type="checkbox" />',
            'keyword' => __('Keyword', 'genwp'),
            'user' => __('Post Author', 'genwp'),
            'category' => __('Category Term', 'genwp'),
            'actions' => __('Actions', 'genwp'),
            'mapping' => __('Keyword Mapping', 'genwp')
        );
    }

    public function column_cb($item) {       
        // Add a checkbox for each keyword in the table
        return sprintf(
            '<input type="checkbox" name="keywords[]" value="%s" />', $item['keyword']
        );    
    }    
    
    public function column_default($item, $column_name) {
        $sanitized_keyword = sanitize_text_field($item['keyword']);
        switch ($column_name) {
            case 'keyword':
                return sprintf('<input type="text" class="keyword-input long-text" data-keyword="%s" value="%s" readonly />', $sanitized_keyword, $sanitized_keyword);
                
            case 'user':
                return $this->generate_dropdown($this->users, $item['user_id'], 'user_select', 'ID', 'display_name', $sanitized_keyword);

            case 'category':
                return $this->generate_dropdown($this->categories, $item['term_id'], 'category-select', 'term_id', 'name', $sanitized_keyword);
            
            case 'actions':
                return sprintf('<a href="#" class="quick-edit-button" data-keyword="%s">Quick Edit</a> <a style="display:none;" href="#" class="quick-save-button" data-keyword="%s">Save</a>', $sanitized_keyword, $sanitized_keyword);         
            
            case 'mapping':
                return sprintf('<input type="submit" name="save_map[%s]" class="button action" value="Save Map"/>', $sanitized_keyword);
            
            default:
                return print_r($item, true);
        }
    }   

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete Keywords',
            'write'    => 'Write Articles',
        );
        return $actions;
    }

    private function process_bulk_action() {
        // if action is 'save_map'
        if (isset($_POST['save_map'])) {
            $keyword = sanitize_text_field(key($_POST['save_map']));
            $user_id = sanitize_text_field($_POST['user_select'][$keyword]);
            $term_id = sanitize_text_field($_POST['category-select'][$keyword]);

            // Update the association in db
            $this->db->update_keyword_mapping($keyword, $user_id, $term_id);

            // Refresh the $this->keywords variable
            $this->keywords = $this->db->get_keywords();        
        } else {

            $action = $this->current_action();

            switch ($action) {
                case 'delete':
                    $keywords_to_delete = array_map('sanitize_text_field', $_REQUEST['keywords']);
                    $this->db->delete_keywords($keywords_to_delete);              
                    break;

                case 'write':
                    $current_keywords = get_option('genwp_selected_keywords', []);

                    $merged_keywords = array_unique(array_merge($current_keywords, $_REQUEST['keywords']));

                    update_option('genwp_selected_keywords', $merged_keywords);

                    break;              
                default:
                    break;
            }
        }
    }
}