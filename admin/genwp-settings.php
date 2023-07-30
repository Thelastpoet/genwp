<?php

namespace genwp;

use genwp\genWP_Db;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class genwp_Settings {
    private $option_name = 'genwp_settings';
    private $page_title = 'OpenAI Settings';
    private $menu_title = 'GenWP';
    private $capability = 'manage_options';
    private $menu_slug = 'genwp-settings';
    private $icon_url = 'dashicons-welcome-write-blog';

    private $genWpdb;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_options_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', [new genwp_KeyPage(), 'registerSettings']);

        add_action('admin_enqueue_scripts', array($this, 'enqueue_genwp_scripts'));

        add_action('admin_init', array($this, 'article_gen'));
        add_action('wp_ajax_genwp_update_keyword', array($this, 'ajax_update_keyword'));
        add_action('wp_ajax_genwp_save_map', array($this, 'ajax_save_map'));

        $this->genWpdb = new genWP_Db();
    }
    
    public function add_options_page() {
        add_menu_page($this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this, 'display_settings_page'), $this->icon_url);
        add_submenu_page($this->menu_slug, 'Article Generator', 'Article Generator', $this->capability, 'genwp-article-generator', array($this, 'display_article_gen'));
        add_submenu_page($this->menu_slug, 'Found Keywords', 'Found Keywords', $this->capability, 'genwp-found-keywords', array($this, 'display_keywords'));
    }

    public function register_settings() {
        register_setting('genwp-openai-settings', $this->option_name);
        
        add_settings_section('openai_settings', 'OpenAI Settings', null, 'genwp-openai-settings');
        
        $fields = array(
            'genwp-openai-api-key' => 'OpenAI API Key',
            'model' => 'OpenAI Model',
            'max_tokens' => 'Maximum Tokens',
            'temperature' => 'Temperature',
            'top_p' => 'Top P',
            'frequency_penalty' => 'Frequency Penalty',
            'presence_penalty' => 'Presence Penalty'
        );

        foreach ($fields as $field => $label) {
            add_settings_field($field, $label, array($this, 'display_field_callback'), 'genwp-openai-settings', 'openai_settings', array('field' => $field));
        }

        // adding new settings for Pexels
        register_setting('genwp-pexels-settings', $this->option_name);

        add_settings_section('pexels_settings', 'Pexels Settings', null, 'genwp-pexels-settings');

        $pexels_fields = array(
            'genwp-pexels-api-key' => 'Pexels API Key'
        );

        foreach ($pexels_fields as $field => $label) {
            add_settings_field($field, $label, array($this, 'display_field_callback'), 'genwp-pexels-settings', 'pexels_settings', array('field' => $field));
        }
    }

    public function display_field_callback($args) {
        $field = $args['field'];
        $option = get_option($this->option_name);
        $value = isset($option[$field]) ? $option[$field] : '';

        if ($field === 'model') {
            $models = array(
                'gpt-4' => 'GPT-4',
                'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K',
                'text-davinci-003' => 'Davinci'
            );
            ?>
            <select name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]">
                <?php foreach ($models as $model_id => $label): ?>
                    <option value="<?php echo esc_attr($model_id); ?>" <?php selected($model_id, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        } else {
            ?>
            <input type="text" name="<?php echo $this->option_name; ?>[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($value); ?>" />
            <?php
        }
    }   

    public function display_settings_page() { ?>
        <div class="wrap">
            <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
            <form action="options.php" method="post">
                <?php 
                settings_fields('genwp-openai-settings');
                do_settings_sections('genwp-openai-settings');

                // adding Pexels settings fields and sections
                settings_fields('genwp-pexels-settings');
                do_settings_sections('genwp-pexels-settings');
                submit_button('Save Settings'); 
                ?>
            </form>
        </div>
        <?php
    } 

    public function article_gen() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_keywords"])) {
            $adminPage = new genwp_KeyPage();
            $adminPage->handleFormSubmission();
        }
    }

    public function display_article_gen() {
        $adminPage = new genwp_KeyPage();
        $adminPage->render();
    }

    public function display_keywords() {
        // Get the saved keywords from database
        $keywords = $this->genWpdb->get_keywords();
        
        // Create and display the keyword list table
        $keywordListTable = new Gen_Key_Table($keywords, $this->genWpdb);
        $keywordListTable->prepare_items();
        
        // Start form here to include keyword list table in form
        echo '<form method="post">';
        
        // Add nonce field for security
        wp_nonce_field('bulk-delete', 'bulk-delete-nonce');
    
        $keywordListTable->display();
    
        // Close the form tag after the table
        echo '</form>';
    }   
    
    public function enqueue_genwp_scripts() {
        wp_enqueue_style('genwp-settings', GENWP_PLUGIN_URL . 'assets/css/genwp-settings.css', array(), GenWP::VERSION);
        wp_enqueue_script('genwp-settings', GENWP_PLUGIN_URL . 'assets/js/genwp-settings.js', array('jquery'), GenWP::VERSION, true);
        wp_enqueue_script('genwp-keygen', GENWP_PLUGIN_URL . 'assets/js/keygen.js', array('jquery'), GenWP::VERSION, true);
    
        wp_localize_script('genwp-keygen', 'genwp_ajax_object', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'updateKeywordNonce' => wp_create_nonce('genwp_update_keyword'),
            'saveMapNonce' => wp_create_nonce('genwp_save_map'),
        ));
    }    

    /**
     * AJAX action handler for updating a keyword.
     */
    public function ajax_update_keyword() {
        error_log('AJAX update keyword callback called.');
        // Check the nonce for security.
        check_ajax_referer('genwp_update_keyword', 'nonce');
    
        // Get the old and new keyword from the request.
        $old_keyword = isset($_POST['old_keyword']) ? sanitize_text_field($_POST['old_keyword']) : '';
        $new_keyword = isset($_POST['new_keyword']) ? sanitize_text_field($_POST['new_keyword']) : '';
    
        // Update the keyword in the database.
        $result = $this->genWpdb->update_keyword($old_keyword, $new_keyword);
    
        if ($result === false) {
            // The update failed due to a database error.
            wp_send_json_error(array('message' => 'Could not update the keyword due to a database error.'));
        } else if ($result === 0) {
            // No rows were updated. This happens when the old keyword doesn't exist in the database.
            wp_send_json_error(array('message' => 'The specified keyword does not exist.'));
        } else {
            // Update keyword in genwp_selected_keywords option
            $selected_keywords = get_option('genwp_selected_keywords', []);
            if(($key = array_search($old_keyword, $selected_keywords)) !== false) {
                $selected_keywords[$key] = $new_keyword;
                update_option('genwp_selected_keywords', $selected_keywords);
            }
    
            // The update was successful. Return the new keyword.
            wp_send_json_success(array('new_keyword' => $new_keyword));
        }
    
        // Always die in functions echoing AJAX content.
        wp_die();
    }

    public function ajax_save_map() {
        check_ajax_referer('genwp_save_map', 'nonce');
    
        // Get the data from the request.
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
        $term_id = isset($_POST['term_id']) ? sanitize_text_field($_POST['term_id']) : '';
    
        // Save the map in the database.
        $result = $this->genWpdb->update_keyword_mapping($keyword, $user_id, $term_id);
    
        if ($result === false) {
            // The save failed due to a database error.
            wp_send_json_error(array('message' => 'Could not save the map due to a database error.'));
        } else {
            // The save was successful.
            wp_send_json_success(array('message' => 'Keyword Mapping Updated Successfully'));
        }
    
        wp_die();
    }
    
}