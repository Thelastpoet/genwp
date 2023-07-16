<?php

namespace genwp;

use genwp\OpenAIGenerator;
use genwp\genWP_Db;
use genwp\genwp_Writer;

class genwp_KeyPage {

    private $openAI;
    private $genWpWriter;
    private $genWpdb;

    public function __construct() {
        $this->openAI = new OpenAIGenerator();
        $this->genWpdb = new genWP_Db();
        $this->genWpWriter = new genwp_Writer($this->openAI, $this->genWpdb);

        add_action( 'wp_ajax_get_terms', array($this, 'genwp_get_terms') );
        add_action( 'init', array($this, 'handle_form_submission'));
    }

    public function handle_form_submission() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {
            // Generate keywords
            $this->genWpWriter->genwp_keywords($_POST);
    
            // Redirect to the "Keywords Found" page
            wp_redirect(admin_url('admin.php?page=genwp-found-keywords'));
            exit;
        }
    }

    public function render() {
        // Display settings form.
        $this->displayForm();
    }

    public function registerSettings() {
        // Register new settings.
        register_setting('genwp-settings-group', 'genwp_post_type');
        register_setting('genwp-settings-group', 'genwp_taxonomy_terms');
        register_setting('genwp-settings-group', 'genwp_assigned_user');

        // Add settings section.
        add_settings_section('genwp-settings-section', 'Generate Keywords', null, 'genwp-article-generator');


        // Add fields to the section.
        add_settings_field('genwp-post-type', 'Post Type', [$this, 'renderPostType'], 'genwp-article-generator', 'genwp-settings-section');
        add_settings_field('genwp-taxonomy-terms', 'Taxonomy Terms', [$this, 'renderTerms'], 'genwp-article-generator', 'genwp-settings-section');
        add_settings_field('genwp-assigned-user', 'Assigned User', [$this, 'renderUserMapping'], 'genwp-article-generator', 'genwp-settings-section');

    }

    public function renderPostType() {
        $post_types = get_post_types();
        ?>
        <select id="genwp-post-type" name="genwp_post_type">
            <?php foreach ($post_types as $post_type) : ?>
                <option value="<?php echo esc_attr($post_type); ?>"><?php echo esc_html($post_type); ?></option>
            <?php endforeach; ?>
        </select>
        <?php    
    }

    public function renderTerms() {
        echo '<div id="renderTerms"></div>';
    }    

    public function renderUserMapping() {
        $args = array(
            'role__in' => array('Editor', 'Author')
        );

        $users = get_users($args);
        ?>
        <select name="genwp_assigned_user">
            <?php foreach ($users as $user) : ?>
                <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }    

    public function displayForm() {
        ?>
        <div class="wrap">
            <h1>Generate Keywords</h1>
            <form method="post" action="">
                <?php
                settings_fields('genwp-article-generator');
                do_settings_sections('genwp-article-generator');
    
                submit_button('Generate Keywords', 'primary', 'submit');
                ?>
            </form>
        </div>
        <?php
    }
    
    public function genwp_get_terms() {
        $post_type = $_POST['post_type'];
        $taxonomies = get_object_taxonomies( $post_type );
        $data = array();
        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms( array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ) );
            $data[ $taxonomy ] = wp_list_pluck( $terms, 'name' );
        }
        wp_send_json_success( $data );
    }

}