<?php

namespace genwp;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use genwp\genWP_Db;

class KeywordsUploader {
    private $genwpdb;

    public function __construct(genWP_Db $genwpdb) {
        $this->genwpdb = $genwpdb;
    }

    public function upload_keywords($tmpFilePath, $taxonomy_term = '', $hasHeader = true) {
        // Check user capability
        if (!current_user_can('upload_files')) {
            throw new \Exception("User does not have permission to upload files.");
        }

        // Move the file to a new location in your plugin directory
        $uploads = wp_upload_dir(); // Get WordPress upload directory info
        $csvFilePath = trailingslashit($uploads['basedir']) . basename($tmpFilePath);
        if (!move_uploaded_file($tmpFilePath, $csvFilePath)) {
            // Failed to move the file, throw an error
            throw new \Exception("Failed to move uploaded file.");
        }

        // Ensure the CSV file is readable
        if (!is_readable($csvFilePath)) {
            throw new \Exception("Cannot read CSV file: $csvFilePath");
        }

        // Open the CSV file for reading
        if (($handle = fopen($csvFilePath, 'r')) !== FALSE) {
            $rowIndex = 0;

            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // If the file has a header row, skip the first row
                if ($hasHeader && $rowIndex == 0) {
                    $rowIndex++;
                    continue;
                }

                // Assume the keyword is in the first column of each row
                $keyword = sanitize_text_field($data[0]);

                // Add the keyword to the database
                $this->genwpdb->saveKeywords(array($keyword));

                $rowIndex++;
            }

            fclose($handle);
        } else {
            throw new \Exception("Failed to open CSV file: $csvFilePath");
        }
    }
}