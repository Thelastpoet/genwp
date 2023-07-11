<?php

namespace genwp;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ContentSettings {
    public $option_name;

    public function __construct($option_name) {
        $this->option_name = $option_name;
    }

    public function build_select($name, $options, $value = null, $multiple = false) {
        ob_start();
        ?>
        <select name="<?php echo esc_attr($name); ?>" <?php echo $multiple ? 'multiple' : ''; ?>>
            <?php foreach ($options as $option_value => $option_label): ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php if ($multiple) { if (is_array($value) && in_array($option_value, $value)) echo 'selected'; } else { selected($option_value, $value); } ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    // Content settings registration
    public function register() {
        register_setting('genwp-content-settings', $this->option_name);
        
        add_settings_section('content_settings', 'Content Settings', null, 'genwp-content-settings');
        
        $fields = array(
            'post_type' => array('label' => 'Post Type', 'type' => 'post_types'),
            'taxonomy' => array('label' => 'Taxonomy', 'type' => 'taxonomies'),
            'items' => array('label' => 'Items', 'type' => 'items')
        );

        foreach ($fields as $field => $config) {
            add_settings_field($field, $config['label'], array($this, 'display_field_callback'), 'genwp-content-settings', 'content_settings', array('field' => $field, 'type' => $config['type']));
        }
    }

    public function display_field_callback($args) {
        $field = $args['field'];
        $type = $args['type'];
        $option = get_option($this->option_name);
        $value = isset($option[$field]) ? $option[$field] : '';
    
        switch ($type) {
            case 'post_types':
                $post_types = get_post_types(array('public' => true), 'objects');
                $options = array();
                foreach ($post_types as $post_type_object) {
                    $options[$post_type_object->name] = $post_type_object->label;
                }
                echo $this->build_select($this->option_name . "[$field]", $options, $value);
                break;
            case 'taxonomies':
                if (!empty($option['post_type'])) {
                    $taxonomies = get_object_taxonomies($option['post_type'], 'objects');
                    $options = array();
                    foreach ($taxonomies as $taxonomy_object) {
                        $options[$taxonomy_object->name] = $taxonomy_object->label;
                    }
                    echo $this->build_select($this->option_name . "[$field]", $options, $value);
                }
                break;
            case 'items':
                if (!empty($option['taxonomy'])) {
                    $terms = get_terms(array(
                        'taxonomy' => $option['taxonomy'],
                        'hide_empty' => false,
                    ));
                    $options = array();
                    foreach ($terms as $term) {
                        $options[$term->term_id] = $term->name;
                    }
                    echo $this->build_select($this->option_name . "[$field][]", $options, $value, true);
                } else {
                    echo 'No taxonomy selected or no terms exist for the selected taxonomy.';
                }
                break;
        }
    }
}
