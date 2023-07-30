<?php

namespace genwp;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use \WP_Error;

class FeaturedImage {
    private $openverse_generator;

    public function __construct( OpenverseImageGenerator $openverse_generator ) {
        $this->openverse_generator = $openverse_generator;
    }

    public function set_featured_image( $keyword, $post_id ) {
        // Generate image with Openverse
        $image_url = $this->openverse_generator->get_image( $keyword );

        if (is_wp_error($image_url)) {
            return new WP_Error('no_image_url', 'No image URL was generated');
        }

        $image_url = esc_url_raw($image_url[0]);

        // Download the image and add it to the media library
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        $result = media_sideload_image( $image_url, $post_id, $keyword, 'id' );

        // Check for errors
        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'download_error', 'Error downloading image' );
        }
    
        // Get the attachment ID of the downloaded image
        $attachments = get_posts( array(
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'orderby' => 'date',
            'order' => 'DESC',
            'numberposts' => 1,
        ) );

        if ( empty( $attachments ) ) {
            return new WP_Error( 'attachment_error', 'Error getting attachment ID' );
        }

        $attach_id = $attachments[0]->ID;

        // Update the author of the attachment
        $author_id = get_post_field( 'post_author', $post_id );
        wp_update_post(
            array(
                'ID' => $attach_id,
                'post_author' => $author_id,
                // No caption since the image is from Openverse and not from Pexels
                'post_excerpt' => '',
            )
        );
    
        // Set the downloaded image as the featured image for the post
        set_post_thumbnail( $post_id, $attach_id );
    }
}