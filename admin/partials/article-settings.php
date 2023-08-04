<?php

namespace genwp;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class genwp_ArticleSettings {
    private $page_title = 'Article Settings';
    private $menu_title = 'Article Settings';
    private $capability = 'manage_options';
    private $menu_slug = 'genwp-article-settings';
    private $option_name = 'genwp_article_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_options_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_options_page() {
        add_submenu_page('genwp-settings', $this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this, 'display_settings_page'));
    }

    public function register_settings() {
        register_setting($this->menu_slug, $this->option_name, array($this, 'sanitize_input'));
    
        add_settings_section('genwp_article_settings', 'Article Settings', null, $this->menu_slug);
    
        $fields = array(
            'genwp_default_author' => array(
                'label' => 'Default Post Author',
                'type' => 'dropdown_users',
                'default' => 1
            ),
            'genwp_default_post_type' => array(
                'label' => 'Default Post Type',
                'type' => 'dropdown_post_types',
                'default' => 'post'
            ),
            'genwp_default_post_status' => array(
                'label' => 'Default Post Status',
                'type' => 'dropdown_post_statuses',
                'default' => 'publish'
            ),
            'genwp_cron_frequency' => array(
                'label' => 'Articles Per Day',
                'type' => 'number',
                'default' => 5
            ),
        );
    
        foreach ($fields as $field => $data) {
            $default = isset($data['default']) ? $data['default'] : '';
            add_settings_field($field, $data['label'], array($this, 'display_field_callback'), $this->menu_slug, 'genwp_article_settings', array('field' => $field, 'type' => $data['type'], 'default' => $default));
        }
    }
    
    public function display_field_callback($args) {
        $field = $args['field'];
        $type = $args['type'];
        $default = isset($args['default']) ? $args['default'] : '';
        $options = get_option($this->option_name);
        $value = isset($options[$field]) ? $options[$field] : $default;
    
        switch ($type) {
            case 'textarea':
                echo '<textarea id="'.esc_attr($field).'" name="'.$this->option_name.'['.esc_attr($field).']">'.esc_textarea($value).'</textarea>';
                break;
    
            case 'dropdown_users':
                $args = array(
                    'show_option_none' => 'Select user',
                    'name' => $this->option_name.'['.esc_attr($field).']',
                    'selected' => $value,
                    'role__in' => array('Administrator', 'Editor', 'Author')
                );
                wp_dropdown_users($args);
                break;                
    
            case 'dropdown_post_types':
                $this->dropdown_select($field, get_post_types(array('public' => true)), $value);
                break;
    
            case 'dropdown_post_statuses':
                $this->dropdown_select($field, get_post_statuses(), $value);
                break;
    
            case 'number':
            case 'text':
                echo '<input type="'.esc_attr($type).'" id="'.esc_attr($field).'" name="'.$this->option_name.'['.esc_attr($field).']" value="'.esc_attr($value).'">';
                
                if($field === 'genwp_cron_frequency') {
                    echo '<p class="description">' . __('Enter a value from 1 to 24. This is the number of times the articles will be generated per day.') . '</p>';
                }
    
                break;
    
            default:
                echo 'Invalid field type';
                break;
        }
    }

    public function display_settings_page() {
        if (!current_user_can($this->capability)) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error('genwp_messages', 'genwp_message', __('Settings Saved', 'genwp'), 'updated');
        }

        settings_errors('genwp_messages');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php 
                settings_fields($this->menu_slug);
                do_settings_sections($this->menu_slug);
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    // A function to handle dropdown selects
    private function dropdown_select($field, $options, $selected_value) {
        echo '<select id="'.esc_attr($field).'" name="'.$this->option_name.'['.esc_attr($field).']">';
        foreach ($options as $option_value => $option_label) {
            $selected = ($option_value === $selected_value) ? 'selected' : '';
            echo '<option value="'.esc_attr($option_value).'" '.$selected.'>'.esc_html($option_label).'</option>';
        }
        echo '</select>';
    }

    public function sanitize_input($input) {
        $sanitized_input = array();
        foreach ($input as $field => $value) {
            switch ($field) {
                case 'genwp_default_author':
                case 'genwp_cron_frequency':
                    $sanitized_input[$field] = min(absint($value), 24);
                    break;

                case 'genwp_default_post_type':
                case 'genwp_default_post_status':
                    $sanitized_input[$field] = sanitize_text_field($value);
                    break;

                default:
                    $sanitized_input[$field] = $value;
            }
        }
        return $sanitized_input;
    }
}