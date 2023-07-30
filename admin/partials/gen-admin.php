<?php

namespace genwp;

use \Exception;

class genwp_KeyPage {

    private $openAI;
    private $genWpWriter;
    private $genWpdb;
    private $featured_image;
    private $openverse_generator;
    private $keywordUploader;
    
    public function __construct() {
        $this->initializeClasses();
        $this->addActions();
    }

    private function initializeClasses() {
        $this->openAI = new OpenAIGenerator();
        $this->genWpdb = new genWP_Db();        
        $this->openverse_generator = new OpenverseImageGenerator('images');
        $this->featured_image = new FeaturedImage($this->openverse_generator);
        $this->keywordUploader = new KeywordsUploader($this->genWpdb);
        $this->genWpWriter = new genwp_Writer($this->openAI, $this->genWpdb, $this->featured_image, $this->openverse_generator);
    }

    private function addActions() {
        add_action('wp_ajax_get_terms', array($this, 'genwp_get_terms'));
        add_action('init', array($this, 'handle_form_submission'));
    }

    public function handle_form_submission() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (isset($_POST["submit"]) && isset($_POST["gen_keywords"])) {
                // Generate keywords
                $this->genWpWriter->genwp_keywords($_POST);
    
                // Redirect to the "Keywords Found" page
                wp_redirect(admin_url('admin.php?page=genwp-found-keywords'));
                exit;
            } elseif (isset($_POST["upload"]) && isset($_FILES["genwp_keyword_file"])) {
                // Handle the file upload
                $tmpFilePath = $_FILES["genwp_keyword_file"]["tmp_name"];
                    
                try {
                    $this->keywordUploader->upload_keywords($tmpFilePath);
    
                    // Redirect to the "Keywords Found" page after successful upload
                    wp_redirect(admin_url('admin.php?page=genwp-found-keywords'));
                } catch (\Exception $e) {
                    // Show an admin notice in WordPress
                    add_action('admin_notices', function() use ($e) {
                        echo '<div class="notice notice-error">';
                        echo '<p>Error uploading keywords: ' . $e->getMessage() . '</p>';
                        echo '</div>';
                    });

                    // Debug: Log the error
                    error_log( "Error uploading keywords: " . $e->getMessage() );
                }
    
                exit;
            }
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
        <div class="wrap genwp-wrap">
            <h1 class="genwp-main-title">Generate Keywords</h1>
            <form method="post" action="" enctype="multipart/form-data" class="genwp-form">
    
                <div class="genwp-section genwp-section-keywords">
                    <?php
                    settings_fields('genwp-article-generator');
                    do_settings_sections('genwp-article-generator');
                    ?>
                    <input type="hidden" name="gen_keywords" value="1">
                    <!-- Added Heading -->
                    <h2 class="genwp-sub-heading">Generate Keywords from Terms</h2>
                    <?php
                    submit_button('Generate Keywords', 'primary genwp-submit-button', 'submit');
                    ?>
                </div>
    
                <div class="genwp-section genwp-section-upload">
                    <!-- Added Heading -->
                    <h2 class="genwp-sub-heading">Upload Keywords CSV</h2>
                    <?php
                    $this->renderFileUpload(); // Include the file upload field
                    submit_button('Upload CSV', 'secondary genwp-upload-button', 'upload');
                    ?>
                </div>
    
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

    public function renderFileUpload() {
        ?>
        <label for="genwp-keyword-file">Upload CSV:</label>
        <input type="file" id="genwp-keyword-file" name="genwp_keyword_file" accept=".csv">
        <p class="description">Please upload a CSV file with one keyword per line.</p>
        <?php
    }    
}