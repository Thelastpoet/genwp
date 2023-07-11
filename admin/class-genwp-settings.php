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

        add_action('admin_enqueue_scripts', array($this, 'enqueue_genwp_scripts'));

        add_action('admin_init', array($this, 'handle_form_submission'));

        $this->genWpdb = new genWP_Db();
    }

    public function enqueue_genwp_scripts() {
        wp_enqueue_style('genwp-settings-css', plugins_url('../assets/css/genwp-settings.css', __FILE__));
        wp_enqueue_script('genwp-settings-js', plugins_url('../assets/js/genwp-settings.js', __FILE__), array('jquery'), null, true);
        
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

    public function add_options_page() {
        add_menu_page($this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this, 'display_settings_page'), $this->icon_url);
        add_submenu_page($this->menu_slug, 'Article Generator', 'Article Generator', $this->capability, 'genwp-article-generator', array($this, 'display_article_generator'));
        add_submenu_page($this->menu_slug, 'Found Keywords', 'Found Keywords', $this->capability, 'genwp-found-keywords', array($this, 'display_found_keywords'));
    }

    public function handle_form_submission() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_keywords"])) {
            error_log('handleFormSubmission called');
            $adminPage = new genwp_KeyPage();
            $adminPage->handleFormSubmission();
        }
    }

    public function display_article_generator() {
        $adminPage = new genwp_KeyPage();
        $adminPage->render();
    }

    public function display_found_keywords() {
        // Get the saved keywords from database
        $keywords = $this->genWpdb->get_keywords();
        
        // Create and display the keyword list table
        $keywordListTable = new Gen_Key_Table($keywords);
        $keywordListTable->prepare_items();
        
        // Start form here to include keyword list table in form
        echo '<form method="post">';
        
        // Add nonce field for security
        wp_nonce_field('bulk-delete', 'bulk-delete-nonce');
    
        $keywordListTable->display();
    
        // Close the form tag after the table
        echo '</form>';
    }   

    public function display_settings_page() { ?>
        <div class="wrap">
            <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
            <form action="options.php" method="post">
                <?php 
                settings_fields('genwp-openai-settings');
                do_settings_sections('genwp-openai-settings');
                submit_button('Save Settings'); 
                ?>
            </form>
        </div>
        <?php
    }   
}