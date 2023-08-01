<?php

namespace genwp;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use genwp\OpenAIGenerator;
use genwp\genWP_Db;
use genwp\FeaturedImage;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class genwp_Writer {
    private $ai_generator;
    private $genwpdb;
    private $featured_image;

    public function __construct(OpenAIGenerator $ai_generator, genWP_Db $genwpdb, FeaturedImage $featured_image) {
        $this->ai_generator = $ai_generator;
        $this->genwpdb = $genwpdb;
        $this->featured_image = $featured_image;
    }  

    public function gen_article($keyword, $num_words = 2500, $args = array()) {
        // Initiate generate articles

        if (!is_array($args)) {
            $args = array();
        }
        
        $args['max_tokens'] = $num_words;

        // Fetch keyword, title, taxonomy_term, user_id and term_id
        $keyword_data = $this->fetch_keyword_data($keyword);

        // Generate the article content and title
        $content_and_title = $this->generate_content($keyword, $args);

        // Separate the title from the content
        $title = $content_and_title['title'];
        $content = $content_and_title['content'];

        // Format the content
        $formatted_content = $this->format_content($content);

        // Create the new post
        $post_id = $this->create_post($formatted_content, $title, $keyword_data, $keyword);

        // Generate and set the featured image for the post
        $this->featured_image->set_featured_image($keyword, $post_id);

        // Delete keyword from the database
        $this->genwpdb->delete_keywords(array($keyword));
        
        return $post_id;
    }

    private function fetch_keyword_data($keyword) {
        // fetch keyword data from DB
        $data = $this->genwpdb->get_keywords();
        $keyword_data = [];
        foreach ($data as $item) {
            if ($item['keyword'] === $keyword) {
                $keyword_data = $item;
                break;
            }
        }

        return $keyword_data;
    }

    private function generate_content($keyword, $args) {
        // We get the article from AI
        $prompt = sprintf("Write a 2000-word SEO-optimized article about '%s'. Begin with a catchy, SEO-optimized level 1 heading (`#`) that captivates the reader. Follow with SEO optimized introductory paragraphs. Then organize the rest of the article into detailed heading tags (`##`) and lower-level heading tags (`###`, `####`, etc.). Include detailed paragraphs under each heading and subheading that provide in-depth information about the topic. Use bullet points, unordered lists, bold text, underlined text, code blocks etc to enhance readability and engagement.", $keyword);
        
        $content = $this->ai_generator->generate_completion($prompt, $args);
    
        // Extract the title from the first level 1 heading
        preg_match('/^#\s+(.*)$/m', $content, $matches);
        $title = $matches[1];

        // Check if the title contains asterisks at the beginning or end, and remove them if found
        if (strpos($title, '*') !== false) {
            $title = preg_replace('/^\*+|\*+$/', '', $title);
        }

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

    private function create_post($content, $title, $keyword_data, $keyword) {    
        $author_id = 1;
        $term_id = null; // Add a default term ID
    
        if ($keyword_data) {
            $author_id = $keyword_data['user_id'];
        
            // Retrieve the term by its ID
            $term = get_term($keyword_data['term_id']);
    
            if (!is_wp_error($term)) {
                $term_id = $term->term_id;
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
        
        if (!is_wp_error($post_id) && $term_id) {
            wp_set_object_terms($post_id, $term_id, 'category');
        }
        
        return $post_id;
    }    
}