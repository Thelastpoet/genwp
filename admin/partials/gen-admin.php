<?php

namespace genwp;

use genwp\OpenAIGenerator;
use genwp\genWP_Db;
use genwp\Gen_Key_Table;

class genwp_KeyPage {

    private $openAI;
    private $genWpdb;
    private $keywords = array();

    public function __construct() {
        $this->openAI = new OpenAIGenerator();
        $this->genWpdb = new genWP_Db();
    }

    public function render() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_keywords"])) {
            error_log('handleFormSubmission called');
            $this->handleFormSubmission();
        }
        $this->displayForm();
    }

    public function handleFormSubmission() {
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        $selectedItems = isset($_POST['selected_terms']) ? array_map('intval', $_POST['selected_terms']) : array();
        $option = array('taxonomy' => $taxonomy, 'items' => $selectedItems);
        $this->generateKeywords($option);
    
        // Append the new keywords to the existing keywords in the 'genwp_selected_keywords' option
        $selected_keywords = get_option('genwp_selected_keywords', []);
        $selected_keywords = array_merge($selected_keywords, $this->keywords);
        update_option('genwp_selected_keywords', $selected_keywords);
    
        // Redirect to the 'Found Keywords' page
        wp_redirect(admin_url('admin.php?page=genwp-found-keywords'));
        exit;
    }   

    private function generateKeywords($option) {
        $taxonomy = $option['taxonomy'];
        $selectedItems = $option['items'];
        
        // Generate keywords for each selected item
        foreach ($selectedItems as $itemId) {
            $item = get_term($itemId, $taxonomy);
            if ($item) {
                $itemKeywords = $this->openAI->generate_keywords($item->name);
                $this->keywords = array_merge($this->keywords, $itemKeywords);

                // Save keywords to the database
                $this->genWpdb->saveKeywords($this->keywords, '', $item->name);
            }
        }        
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
    
        ?>
        <div class="wrap">
            <h1>Generate Keywords</h1>
            <form method="post" name="generate_keywords">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="post_type">Select Post Type:</label>
                        </th>
                        <td>
                            <select name="post_type" id="post_type">
                                <?php
                                foreach ($postTypes as $type) {
                                    echo '<option value="' . esc_attr($type->name) . '"' . selected($postType, $type->name, false) . '>' . esc_html($type->labels->singular_name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
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