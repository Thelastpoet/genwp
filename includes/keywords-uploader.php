<?php

namespace genwp;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use genwp\genWP_Db;

require_once(ABSPATH . 'wp-admin/includes/file.php');

class KeywordsUploader {
    private $genwpdb;

    public function __construct(genWP_Db $genwpdb) {
        $this->genwpdb = $genwpdb;
    }

    public function upload_keywords($file_array, $taxonomy_term = '', $hasHeader = true) {
        // Check user capability
        if (!current_user_can('upload_files')) {
            return new \WP_Error('permission_denied', 'You do not have permission to upload files.');
        }
    
        // Validate file type
        $path_info = pathinfo($file_array['name']);
        $ext = isset($path_info['extension']) ? $path_info['extension'] : '';
    
        if ($ext !== 'csv') {
            return new \WP_Error('invalid_file_type', 'Please upload a valid CSV file.');
        }
    
        // Handle the uploaded file
        $overrides = array('test_form' => false);
        $file = wp_handle_upload($file_array, $overrides);
    
        if (isset($file['error'])) {
            // The uploaded file could not be moved or there was an error in the upload process.
            return new \WP_Error('file_upload_failed', $file['error']);
        }
    
        $csvFilePath = $file['file'];
    
        // Ensure the CSV file is readable
        if (!is_readable($csvFilePath)) {
            return new \WP_Error('file_not_readable', "Cannot read CSV file: $csvFilePath");
        }
    
        // Open the CSV file for reading
        if (($handle = fopen($csvFilePath, 'r')) !== FALSE) {
            $rowIndex = 0;
            $keywordsProcessed = 0;
            $keywordsSkipped = 0;
    
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // If the file has a header row, skip the first row
                if ($hasHeader && $rowIndex == 0) {
                    $rowIndex++;
                    continue;
                }
    
                // Ignore empty rows
                if (empty($data[0])) {
                    $keywordsSkipped++;
                    continue;
                }
    
                // Assume the keyword is in the first column of each row
                $keyword = sanitize_text_field($data[0]);
    
                // Add the keyword to the database
                $this->genwpdb->saveKeywords(array($keyword));
    
                $rowIndex++;
                $keywordsProcessed++;
            }
    
            fclose($handle);
    
            // Delete the file after reading
            unlink($csvFilePath);
    
            // Return success message with stats
            return array(
                'message' => 'Keywords uploaded successfully!',
                'keywordsProcessed' => $keywordsProcessed,
                'keywordsSkipped' => $keywordsSkipped
            );
    
        } else {
            return new \WP_Error('file_open_failed', "Failed to open CSV file: $csvFilePath");
        }
    }    
}