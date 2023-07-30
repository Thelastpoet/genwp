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

    public function genwp_keywords($option) {
        // Generate keywords for each selected term and assign user to it (We won't need it soon)
        $taxonomy = 'category';
        $selectedTerms = $option['genwp_taxonomy_terms'];
        $assignedUser = $option['genwp_assigned_user'];
    
        foreach ($selectedTerms as $termName) {
            $term = get_term_by('name', $termName, $taxonomy);
            if ($term) {
                $termKeywords = $this->ai_generator->generate_keywords($term->name);    
                $this->genwpdb->saveKeywords($termKeywords, '', $term->name);    
                update_term_meta($term->term_id, 'assigned_user', $assignedUser);
            }
        }
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
        $prompt = sprintf("Write a 2500-word SEO-optimized article about '%s'. Begin with a catchy, SEO-optimized level 1 heading (`#`) that includes power words to captivate the reader. Follow with several introductory paragraphs that provide an overview of the topic. Organize the rest of the article into at least 10 sections using heading tags (`##`) and include sub-sections as needed using lower-level heading tags (`###`, `####`, etc.). Each section should be approximately 450 words in length. Include detailed paragraphs under each heading that provide in-depth information about the topic. Use bullet points, unordered lists, bold text, underlined text, and code blocks to enhance readability and engagement.", $keyword);
        
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
        // Let's now write the article

        // Set a default author ID
        $author_id = 1;
    
        if ($keyword_data) {
            $author_id = $keyword_data['user_id'];
    
            // Retrieve the term by its ID
            $term = get_term($keyword_data['term_id']);
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
        
        if (!is_wp_error($post_id) && !empty($term)) {
            wp_set_object_terms($post_id, $term->term_id, $term->taxonomy);
            $def_term_id = 1;
            wp_remove_object_terms($post_id, $def_term_id, 'category');
        }
        
        return $post_id;
    }        
}