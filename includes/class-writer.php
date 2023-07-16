<?php

namespace genwp;

use genwp\OpenAIGenerator;
use genwp\genWP_Db;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class genwp_Writer {
    private $ai_generator;
    private $genwpdb;

    public function __construct(OpenAIGenerator $ai_generator, genWP_Db $genwpdb) {
        $this->ai_generator = $ai_generator;
        $this->genwpdb = $genwpdb;
    }

    public function genwp_keywords($option) {
        $taxonomy = 'category';
        $selectedTerms = $option['genwp_taxonomy_terms'];
        $assignedUser = $option['genwp_assigned_user'];
    
        // Generate keywords for each selected term and assign user to it
        foreach ($selectedTerms as $termName) {
            $term = get_term_by('name', $termName, $taxonomy);
            if ($term) {
                $termKeywords = $this->ai_generator->generate_keywords($term->name);
    
                // Save keywords to the database
                $this->genwpdb->saveKeywords($termKeywords, '', $term->name);
    
                // Assign user to term
                update_term_meta($term->term_id, 'assigned_user', $assignedUser);
            }
        }
    }    

    public function gen_article($keyword, $num_words = 2500, $args = array()) {
        if (!is_array($args)) {
            $args = array();
        }
        
        $args['max_tokens'] = $num_words;

        // Fetch keyword, title, and taxonomy_term
        $taxonomy_term = $this->fetch_taxonomy_term($keyword);

        // Generate the article content and title
        $content_and_title = $this->generate_content($keyword, $args);

        // Separate the title from the content
        $title = $content_and_title['title'];
        $content = $content_and_title['content'];

        // Format the content
        $formatted_content = $this->format_content($content);

        // We will avoid hardcoding this later Please remember me...
        $valid_taxonomy = 'category';

        // Create the new post
        $post_id = $this->create_post($formatted_content, $title, $taxonomy_term, $valid_taxonomy, $keyword);

        // Generate and set the featured image for the post
        $this->set_featured_image($keyword, $post_id);

        // Delete keyword from the database
        $this->genwpdb->delete_keywords(array($keyword));
        
        return $post_id;
    }

    private function fetch_taxonomy_term($keyword) {
        $data = $this->genwpdb->get_keywords();
        $taxonomy_term = '';
        foreach ($data as $item) {
            if ($item['keyword'] === $keyword) {
                $taxonomy_term = $item['taxonomy_term'];
                break;
            }
        }

        return $taxonomy_term;
    }

    private function generate_content($keyword, $args) {
        // Modified prompt
        $prompt = "Write a 2500-word well-structured, SEO-optimized article about '{$keyword}' in Markdown format, using CommonMark syntax. Start with a catchy, SEO-optimized level 1 heading (`#`) that includes power words to engage the reader. Use other heading tags (such as `##`, `###`, etc.) to organize the content into sections and sub-sections. Include headings, subheadings, tables, bullet points, etc. You must include two to three SEO optimized introductory paragraphs, and detailed three to five paragraphs under each unnumbered heading and subheading.";
    
        $content = $this->ai_generator->generate_completion($prompt, $args);
    
        // Extract the title from the first level 1 heading
        preg_match('/^#\s+(.*)$/m', $content, $matches);
        $title = $matches[1];
        $content = preg_replace('/^#\s+.*$\n/m', '', $content);
    
        return array('title' => $title, 'content' => $content);
    }    

    private function format_content($content) {
        // Use CommonMark to format the content
        $converter = new GithubFlavoredMarkdownConverter();

        $formatted_content = $converter->convertToHtml($content);

        // Ensure that content is a string
        if (is_object($formatted_content)) {
            $formatted_content = (string) $formatted_content;
        }

        return $formatted_content;
    } 

    private function create_post($content, $title, $taxonomy_term, $valid_taxonomy, $keyword) {
        // Retrieve the term by its name
        $term = get_term_by('name', $taxonomy_term, $valid_taxonomy);
    
        // Set a default author ID
        $author_id = get_term_meta($term->term_id, 'assigned_user', true);
    
        if ($term) {
            // If there is no user assigned in the settings, use the default one
            if (empty($author_id)) {
                $author_id = 1;
            }
        }
    
        $slug = sanitize_title($keyword);
    
        $postarr = array(
            'post_title'    => wp_strip_all_tags($title),
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'post',
            'post_author'   => $author_id,
            'post_name'     => $slug,
        );
    
        $post_id = wp_insert_post($postarr);
    
        if (!is_wp_error($post_id) && !empty($taxonomy_term)) {
            wp_set_object_terms($post_id, array($taxonomy_term), $valid_taxonomy);
    
            $def_term_id = 1;
    
            wp_remove_object_terms($post_id, $def_term_id, $valid_taxonomy);
        }
    
        return $post_id;
    }

    public function set_featured_image( $keyword, $post_id ) {
        $description = "Generate a realistic high quality image depicting the concept of '{$keyword}', preferably in vibrant colors.";
    
        // Generate image with OpenAI
        $image_urls = $this->ai_generator->generate_image( $description );
    
        if ( ! is_array( $image_urls ) || count( $image_urls ) === 0 ) {
            return new WP_Error( 'no_image_url', 'No image URL was generated' );
        }
    
        $image_url = esc_url_raw( $image_urls[0] );
    
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
            )
        );
    
        // Set the downloaded image as the featured image for the post
        set_post_thumbnail( $post_id, $attach_id );
    }  
    
}