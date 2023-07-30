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

        $db = new \genwp\genWP_Db(); 
        $this->process_bulk_action($db);

        $this->keywords = $keywords;
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
        $db = new \genwp\genWP_Db();
    
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
        switch ($column_name) {
            case 'keyword':
            // Form input
            return sprintf('<input type="text" class="keyword-input long-text" data-keyword="%s" value="%s" readonly />', sanitize_text_field($item['keyword']), sanitize_text_field($item['keyword']));
                
            case 'user':
                // Dropdown with users
                $users = get_users();
                return $this->generate_dropdown($users, $item['user_id'], 'user_select', 'ID', 'display_name', $item['keyword']);

            case 'category':
                // Dropdown with categories
                $args = array(
                    'taxonomy' => 'category',
                    'hide_empty' => false,
                );
                $categories = get_terms($args);
                return $this->generate_dropdown($categories, $item['term_id'], 'category-select', 'term_id', 'name', $item['keyword']);
            
            case 'actions':
                // Display a "Quick Edit" and "Save" button
                return sprintf('<a href="#" class="quick-edit-button" data-keyword="%s">Quick Edit</a> <a style="display:none;" href="#" class="quick-save-button" data-keyword="%s">Save</a>', $item['keyword'], $item['keyword']);         
            
            case 'mapping':
                return sprintf('<input type="submit" name="save_map[%s]" class="button action" value="Save Map"/>', $item['keyword']);
            
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

    private function process_bulk_action($db) {
        // if action is 'save_map'
        if (isset($_POST['save_map'])) {

            // Get the keyword
            $keyword = key($_POST['save_map']);

            // Get new user_id and term_id
            $user_id = $_POST['user_select'][$keyword];
            $term_id = $_POST['category-select'][$keyword];

            // Update the association in db
            $db->update_keyword_mapping($keyword, $user_id, $term_id);

            // Refresh the $this->keywords variable
            $this->keywords = $db->get_keywords();        
        } else {

            $action = $this->current_action();

            switch ($action) {
                case 'delete':
                    $db->delete_keywords($_REQUEST['keywords']);                
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