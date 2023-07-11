<?php

namespace genwp;

use genwp\OpenAIGenerator;
use genwp\genWP_Db;

use League\CommonMark\CommonMarkConverter;

class genwp_Writer {
    private $ai_generator;
    private $genwpdb;

    public function __construct(OpenAIGenerator $ai_generator, genWP_Db $genwpdb) {
        $this->ai_generator = $ai_generator;
        $this->genwpdb = $genwpdb;
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
        $post_id = $this->create_post($formatted_content, $title, $taxonomy_term, $valid_taxonomy);

        if (is_wp_error($post_id)) {
            // Handle error
            // For now, we just return the error. We will create logs later.
            return $post_id;
        }

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
        // Our good prompt
        $prompt = "Write a 2000-word well-structured, SEO-optimized blog post about '{$keyword}' in Markdown format, using CommonMark syntax. Use heading tags (such as `#`, `##`, etc.) to organize the content into sections and sub-sections. Include headings, subheadings, tables, bullet points, and numbered lists. You should include 2 to 3 SEO optimized introductory paragraphs, a few sentences, a few paragraphs, and detailed two to three paragraphs under each unnumbered heading and subheading. Also, before the concluding paragraph, include a section of 5 Frequently Asked Questions related to the topic.";

        $content = $this->ai_generator->generate_completion($prompt, $args);

        // Extract the title from the first level 1 heading
        preg_match('/^#\s+(.*)$/m', $content, $matches);
        $title = $matches[1];
        $content = preg_replace('/^#\s+.*$\n/m', '', $content);

        return array('title' => $title, 'content' => $content);
    }

    private function create_post($content, $title, $taxonomy_term, $valid_taxonomy) {
        // Retrieve a list of existing users
        $authors = get_users(array('role' => 'author'));

        // Select a random author from the list
        $random_author = $authors[array_rand($authors)];

        // Set the author ID
        $author_id = $random_author->ID;

        $postarr = array(
            'post_title'    => wp_strip_all_tags($title),
            'post_content'  => $content,
            'post_status'   => 'draft',
            'post_type'     => 'post',
            'post_author'   => $author_id,
        );

        $post_id = wp_insert_post($postarr);

        // If wp_insert_post has been completed successfully and term is not empty
        if (!is_wp_error($post_id) && !empty($taxonomy_term)) {
            wp_set_object_terms($post_id, array($taxonomy_term), $valid_taxonomy);

            // Let us remove the category WordPress adds to the article
            $def_term_id = 1;

            wp_remove_object_terms($post_id, $def_term_id, $valid_taxonomy);
        }

        return $post_id;
    }

    private function format_content($content) {
        // Use CommonMark to format the content
        $converter = new CommonMarkConverter();

        $formatted_content = $converter->convertToHtml($content);

        // Ensure that content is a string
        if (is_object($formatted_content)) {
            $formatted_content = (string) $formatted_content;
        }

        return $formatted_content;
    } 
    
}