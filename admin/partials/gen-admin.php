<?php

namespace genwp;

use \Exception;

class genwp_KeyPage {

    private $genWpdb;
    private $keywordUploader;
    
    public function __construct() {
        $this->genWpdb = new genWP_Db();        
        $this->keywordUploader = new KeywordsUploader($this->genWpdb);

        add_action('init', array($this, 'handleFormSubmission'));
    }

    public function handleFormSubmission() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload"]) && isset($_FILES["genwp_keyword_file"])) {
            // Handle the file upload
            $file_array = $_FILES["genwp_keyword_file"];    
            try {
                $uploadResult = $this->keywordUploader->upload_keywords($file_array);    
                if (is_wp_error($uploadResult)) {    
                    // Store the error message in a transient
                    set_transient('genwp_upload_error', $uploadResult->get_error_message(), 45);
                } else {
                    // Redirect to the "Keywords Found" page after successful upload
                    wp_redirect(admin_url('admin.php?page=genwp-found-keywords'));
                    exit;
                }
            } catch (\Exception $e) {    
                // Store the error message in a transient
                set_transient('genwp_upload_error', $e->getMessage(), 45);
            }
        }
    }    

    public function render() {
        // Check for an upload error
        $error_message = get_transient('genwp_upload_error');
        if ($error_message) {
            // Display the error and delete the transient
            echo '<div class="notice notice-error">';
            echo '<p>Error uploading keywords: ' . esc_html($error_message) . '</p>';
            echo '</div>';
            delete_transient('genwp_upload_error');
        }
        
        ?>
        <div class="wrap genwp-wrap">
            <h1 class="genwp-main-title">Upload Keywords</h1>
            <form method="post" action="" enctype="multipart/form-data" class="genwp-form">
                <div class="genwp-section genwp-section-upload">
                    <h2 class="genwp-sub-heading">Upload Keywords CSV</h2>                    
                    <!-- File upload -->
                    <label for="genwp-keyword-file">Upload CSV:</label>
                    <input type="file" id="genwp-keyword-file" name="genwp_keyword_file" accept=".csv">
                    <p class="description">Please upload a CSV file with one keyword per line.</p>
    
                    <?php submit_button('Upload CSV', 'secondary genwp-upload-button', 'upload'); ?>
                </div>
            </form>
        </div>
        <?php
    }    
}