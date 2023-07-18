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
        // Revised prompt
        $prompt = "Write a 3000-word SEO-optimized article about '{$keyword}' using the following guidelines:\n\n- Start with two to three SEO-optimized introductory paragraphs that provide an overview of '{$keyword}'.\n- Use a human-like writing style and incorporate personification to add more emotive language and bring out a more expressive tone.\n- Always use Active Voice and vary sentence lengths and use different sentence structures to create an interesting flow and pace.\n- Use Markdown format, specifically the CommonMark syntax.\n- Begin with a catchy, SEO-optimized level 1 heading (`#`) that includes power words to captivate the reader.\n- Organize the content into sections and sub-sections using other heading tags (`##`, `###`, etc.).\n- Use tables, bullet points, unordered lists, bold text, underlined text, and code blocks to present information in a structured and organized manner.\n- Include detailed three to five paragraphs under each heading and subheading that provide in-depth information about the topic.\n";
        
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

    private function create_post($content, $title, $taxonomy_term, $valid_taxonomy, $keyword) {
        // Retrieve the term by its name
        $term = get_term_by('name', $taxonomy_term, $valid_taxonomy);
    
        // Set a default author ID
        $author_id = 1;
    
        if ($term) {
            $assigned_user = get_term_meta($term->term_id, 'assigned_user', true);

            // If there is a user assigned in settings, we use that user
            if ( !empty($assigned_user)) {
                $author_id = $assigned_user;
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
}