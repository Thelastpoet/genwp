<?php

namespace GenWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use GenWP\genWP_Db;
use League\Csv\Reader;
use WP_REST_Request;
use WP_REST_Response;

require_once(ABSPATH . 'wp-admin/includes/file.php');

/**
 * Class KeywordsUploader
 * Handles the uploading and processing of keywords from a CSV file.
 *
 * @package GenWP
 */
class KeywordsUploader {
    private $genwpdb;

    public function __construct(genWP_Db $genwpdb) {
        $this->genwpdb = $genwpdb;
    }

    /**
     * Uploads keywords from a CSV file.
     *
     * @param WP_REST_Request $request The request object.
     * @return array|WP_REST_Response The response object or array.
     */
    public function upload_keywords(WP_REST_Request $request) {
        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return $this->error_response('No file was uploaded.', 400);
        } 
    
        $file_array = $files['file'];

        // Extract other parameters from the request if needed
        $hasHeader = $request->get_param('hasHeader') === 'true';
    
        // Check user capability
        if (!current_user_can('upload_files')) {
            return $this->error_response('You do not have permission to upload files.', 403);
        }   
    
        // Validate file type
        $validationResult = $this->validate_file($file_array);
        if ($validationResult !== true) {
            return $validationResult;
        }

        // Handle the uploaded file
        $file = $this->handle_file_upload($file_array);
        if (is_a($file, 'WP_REST_Response')) {
            return $file;
        }

        $csvFilePath = $file['file'];

        return $this->process_csv_file($csvFilePath, $hasHeader);
    }

    /**
     * Returns an error response.
     * 
     * @param string $message The error message.
     * @param int $status The HTTP status code.
     * @return WP_REST_Response The error response object.
    */

    private function error_response(string $message, int $status): WP_REST_Response {
        return new WP_REST_Response(['success' => false, 'message' => $message], $status);
    }

    /**
     * Validates the uploaded file.
     * @param array $file_array The file array.
     * @return true|WP_REST_Response True if the file is valid, error response otherwise.
    */
    private function validate_file(array $file_array) {
        $path_info = pathinfo($file_array['name']);
        $ext = isset($path_info['extension']) ? $path_info['extension'] : '';

        if ($ext !== 'csv') {
            return $this->error_response('Please upload a valid CSV file.', 400);
        }

        return true;
    }
    
    /**
     * Handles the file upload.
     *
     * @param array $file_array The file array.
     * @return array|WP_REST_Response The file data if successful, error response otherwise.
    */

    private function handle_file_upload(array $file_array) {
        $overrides = ['test_form' => false];
        $file = wp_handle_upload($file_array, $overrides);

        if (isset($file['error'])) {
            return $this->error_response($file['error'], 400);
        }
        return $file;
    }

    /**
     * Processes the CSV file.
     *
     * @param string $csvFilePath The path to the CSV file.
     * @param bool $hasHeader Whether the CSV file has a header row.
     * @return array|WP_REST_Response The success response if successful, error response otherwise.
    */

    private function process_csv_file(string $csvFilePath, bool $hasHeader) {
        if (!is_readable($csvFilePath)) {
            return $this->error_response("Cannot read CSV file: $csvFilePath", 400);
        }
        
        try {
            // Load CSV file using League\Csv\Reader
            $csv = Reader::createFromPath($csvFilePath, 'r');
            $csv->setHeaderOffset($hasHeader ? 0 : null);

            $keywordsBatch = [];
            $batchSize = 100;
            $keywordsProcessed = 0;
            $keywordsSkipped = 0;

            foreach ($csv as $record) {
                $keyword = sanitize_text_field($record['keyword']);
                if (empty($keyword)) {
                    $keywordsSkipped++;
                    continue;
                }

                $keywordsBatch[] = $keyword;
                $keywordsProcessed++;

                if (count($keywordsBatch) >= $batchSize) {
                    $this->genwpdb->saveKeywords($keywordsBatch);
                    $keywordsBatch = [];
                }
            }

            if (!empty($keywordsBatch)) {
                $this->genwpdb->saveKeywords($keywordsBatch);
            }
            
            unlink($csvFilePath);
        
            // Return success message with stats
            return array(
                'success' => true,
                'message' => 'Your keywords have been uploaded successfully! Find them in Keywords Tab',
                'keywordsProcessed' => $keywordsProcessed,
                'keywordsSkipped' => $keywordsSkipped
            );
        
        } catch (\Exception $e) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => $e->getMessage()
                ), 400);
        } finally {
            if (file_exists($csvFilePath)) {
                unlink($csvFilePath);
            }
            
        }
    }
}