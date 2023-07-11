<?php

namespace genwp;

use genwp\OpenAIGenerator;
use genwp\genWP_Db;

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
        $data = $this->genwpdb->get_keywords();
        $taxonomy_term = '';
        foreach ($data as $item) {
            if ($item['keyword'] === $keyword) {
                $taxonomy_term = $item['taxonomy_term'];
                break;
            }
        }

        // Use the keyword argument instead of pulling from the database
        $prompt = "Write a 2000-word well-structured, SEO-optimized blog post about '{$keyword}'. Use heading tags (such as h2, h3, etc.) to organize the content into sections and sub-sections. Include headings, subheadings, tables, bullet points, and numbered lists. You should include 2 to 3 SEO optimized introductory paragraphs, a few sentences, a few paragraphs, and detailed two to three paragraphs under each unnumbered heading and subheading. Also, before the concluding paragraph, include a section of 5 Frequently Asked Questions related to the topic.";

        $content = $this->ai_generator->generate_completion($prompt, $args);

        preg_match('/Title: (.*)\n/', $content, $matches);
        $title = $matches[1];
        $content = str_replace("Title: {$title}\n", '', $content);

        // Format the content
        $content = $this->format_content($content);

        // We will avoid hardcoding this later Please remember me...
        $valid_taxonomy = 'category';

        // Retrieve a list of existing authors
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

        // Delete keyword from the database
        $this->genwpdb->delete_keywords(array($keyword));
        
        return $post_id;
    }    

    private function format_content($content) {
        // Convert markdown headers to HTML
        $content = preg_replace('/^([IVXLCDM]+)\. (.*?)(\n|$)/m', '<h2>$2</h2>', $content);

        // Convert markdown lists to HTML
        $content = preg_replace('/^(\d+)\. (.*?)(\n|$)/m', '<h3>$2</h3>', $content);

        return $content;
    }
}