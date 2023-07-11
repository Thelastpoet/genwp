<?php

namespace genwp;

use genwp\OpenAIGenerator;
use genwp\genWP_Db;
use genwp\Gen_Key_Table;
use genwp\genwp_Writer;

class genwp_KeyPage {

    private $openAI;
    private $genWpWriter;
    private $genWpdb;
    private $keywords = array();

    public function __construct() {
        $this->openAI = new OpenAIGenerator();
        $this->genWpdb = new genWP_Db();
        $this->genWpWriter = new genwp_Writer($this->openAI, $this->genWpdb);
    }

    public function render() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_keywords"])) {
            $this->handleFormSubmission();
        }
        $this->displayForm();
    }

    public function handleFormSubmission() {
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        $selectedItems = isset($_POST['selected_terms']) ? array_map('intval', $_POST['selected_terms']) : array();
        $option = array('taxonomy' => $taxonomy, 'items' => $selectedItems);
        $this->genWpWriter->genwp_keywords($option);
    
        // Append the new keywords to the existing keywords in the 'genwp_selected_keywords' option
        $selected_keywords = get_option('genwp_selected_keywords', []);
        $selected_keywords = array_merge($selected_keywords, $this->keywords);
        update_option('genwp_selected_keywords', $selected_keywords);
    
        // Redirect to the 'Found Keywords' page
        wp_redirect(admin_url('admin.php?page=genwp-found-keywords'));
        exit;
    }   

    private function displayForm() {
        $postTypes = get_post_types(array('public' => true), 'objects');
        $postType = isset($_POST["post_type"]) ? sanitize_text_field($_POST["post_type"]) : '';
    
        // If $postType is empty or not a valid post type, set the first available post type as the default
        if (empty($postType) || !isset($postTypes[$postType])) {
            $postType = !empty($postTypes) ? key($postTypes) : '';
        }
    
        // Retrieve all taxonomies associated with the selected post type.
        $taxonomies = array();
        if (!empty($postType)) {
            $taxonomies = get_object_taxonomies($postType, 'objects');
        }
    
        // Check if there are selected terms in the options, else initialize as an empty array.
        $selectedTerms = isset($_POST["selected_terms"]) ? $_POST["selected_terms"] : array();
    
        // Prepare a message for the user.
        $message = 'Select the terms in the taxonomies for the post type "' . $postType . '":';
    
        // Get all users.
        $users = get_users();

        ?>
        <div class="wrap">
            <h1>Generate Keywords</h1>
            <form method="post" name="generate_keywords">
                <table class="form-table" role="presentation">
                    <tr>
                        <!-- Rest of the form -->
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php echo esc_html($message); ?></label>
                        </th>
                        <td>
                            <?php foreach ($taxonomies as $taxonomy) {
                                $terms = get_terms(array(
                                    'taxonomy'   => $taxonomy->name,
                                    'hide_empty' => false,
                                ));
                                ?>
                                <h4><?php echo esc_html($taxonomy->label); ?></h4>
                                <?php
                                foreach ($terms as $term) { ?>
                                    <label>
                                        <input type="checkbox" name="selected_terms[]" value="<?php echo esc_attr($term->term_id); ?>" 
                                            <?php echo in_array($term->term_id, $selectedTerms) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($term->name); ?>
                                    </label>

                                    <select name="assigned_user[]">
                                        <?php
                                        foreach ($users as $user) {
                                            echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                                        }
                                        ?>
                                    </select>

                                    <br>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
    
                <p class="submit">
                    <input type="submit" name="generate_keywords" id="submit" class="button button-primary" value="Generate Keywords">
                </p>
            </form>
        </div>
        <?php
    }
    
}