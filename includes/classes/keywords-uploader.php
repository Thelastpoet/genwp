<?php

namespace GenWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use GenWP\genWP_Db;
use WP_REST_Request;
use WP_REST_Response;

require_once(ABSPATH . 'wp-admin/includes/file.php');

class KeywordsUploader {
    private $genwpdb;

    public function __construct(genWP_Db $genwpdb) {
        $this->genwpdb = $genwpdb;
    }

    public function upload_keywords(WP_REST_Request $request) {
        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No file was uploaded.'
            ), 400);
        }        
    
        $file_array = $files['file'];

        // Extract other parameters from the request if needed
        $taxonomy_term = $request->get_param('taxonomy_term');
        $hasHeader = $request->get_param('hasHeader') === 'true';
    
        // Check user capability
        if (!current_user_can('upload_files')) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'You do not have permission to upload files.'
            ), 403);
        }        
    
        // Validate file type
        $path_info = pathinfo($file_array['name']);
        $ext = isset($path_info['extension']) ? $path_info['extension'] : '';
    
        if ($ext !== 'csv') {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Please upload a valid CSV file.'
            ), 400);
        }        
    
        // Handle the uploaded file
        $overrides = array('test_form' => false);
        $file = wp_handle_upload($file_array, $overrides);
    
        if (isset($file['error'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $file['error']
            ), 400);
        }        
    
        $csvFilePath = $file['file'];
    
        // Ensure the CSV file is readable
        if (!is_readable($csvFilePath)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => "Cannot read CSV file: $csvFilePath"
            ), 400);
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
                'success' => true,
                'message' => 'Your keywords have been uploaded successfully! Find them in Keywords Tab',
                'keywordsProcessed' => $keywordsProcessed,
                'keywordsSkipped' => $keywordsSkipped
            );
    
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => "Failed to open CSV file: $csvFilePath"
            ), 400);
        }
    }    
}