<?php

namespace GenWP;

use \WP_Error;

class FeaturedImage {
    private $image_generator;

    public function __construct(ImageGenerator $image_generator) {
        $this->image_generator = $image_generator;
    }

    public function set_featured_image($keyword, $post_id) {
        // Generate image
        $image_data = $this->image_generator->pexels_generate_image($keyword);

        if (is_wp_error($image_data)) {
            return new WP_Error('no_image_data', 'No image data was generated');
        }

        $image_url = esc_url_raw($image_data['image_url']);

        // Include the necessary WordPress files
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download the image to a temporary location
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return new WP_Error('download_error', 'Error downloading image');
        }

        // Prepare an array of post data for the attachment.
        $file_array = array(
            'name' => sanitize_title($keyword) . '.jpeg',
            'tmp_name' => $tmp
        );

        // Insert the image into the media library and attach it to the post
        $id = media_handle_sideload($file_array, $post_id, $keyword);

        // Check for errors
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return new WP_Error('sideload_error', 'Error sideloading image');
        }

        // Get the post author
        $author_id = get_post_field('post_author', $post_id);

        // Update attachment
        wp_update_post(array(
            'ID' => $id,
            'post_author' => $author_id,
        ));

        // Set ALT text
        update_post_meta($id, '_wp_attachment_image_alt', $keyword);

        // Set the downloaded image as the featured image for the post
        set_post_thumbnail($post_id, $id);
    }
}
