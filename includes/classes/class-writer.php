<?php

namespace GenWP;

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

    public function generateArticle($keyword, $numWords = 8000, $args = []) {
        $args['max_tokens'] = $numWords;
        
        $keywordData = $this->fetchKeywordData($keyword);
        $contentAndTitle = $this->generateContent($keyword, $args);

        $title = $contentAndTitle['title'];
        $content = $contentAndTitle['content'];

        $formattedContent = $this->formatContent($content);

        $postId = $this->createPost($formattedContent, $title, $keywordData, $keyword);

        $this->featured_image->setFeaturedImage($keyword, $postId);

        $this->genwpdb->deleteKeywords([$keyword]);
        
        return $postId;
    }

    private function fetchKeywordData($keyword) {
        $data = $this->genwpdb->getKeywords();
        
        foreach ($data as $item) {
            if ($item['keyword'] === $keyword) {
                return $item;
            }
        }

        return [];
    }

    private function generateContent($keyword, $args) {
        $prompt = sprintf("Write a %d-word SEO-optimized article about '%s'. Begin with a catchy, SEO-optimized level 1 heading (`#`) that captivates the reader. Follow with SEO optimized introductory paragraphs. Then organize the rest of the article into detailed heading tags (`##`) and lower-level heading tags (`###`, `####`, etc.). Include detailed paragraphs under each heading and subheading that provide in-depth information about the topic. Use bullet points, unordered lists, bold text, underlined text, code blocks etc to enhance readability and engagement.", $args['max_tokens'], $keyword);
        
        $content = $this->ai_generator->generateCompletion($prompt, $args);

        preg_match('/^#\s+(.*)$/m', $content, $matches);
        $title = $matches[1];

        if (strpos($title, '*') !== false) {
            $title = preg_replace('/^\*+|\*+$/', '', $title);
        }

        $content = preg_replace('/^#\s+.*$\n/m', '', $content);
    
        return ['title' => $title, 'content' => $content];
    }

    private function formatContent($content) {
        $converter = new GithubFlavoredMarkdownConverter();
        $formattedContent = $converter->convertToHtml($content);

        if (is_object($formattedContent)) {
            $formattedContent = (string) $formattedContent;
        }

        return $formattedContent;
    }

    private function createPost($content, $title, $keywordData, $keyword) {
        $settings = get_option('genwp_article_settings', []);

        $defaultAuthor = isset($settings['genwp_default_author']) ? $settings['genwp_default_author'] : 1;
        $defaultPostType = isset($settings['genwp_default_post_type']) ? $settings['genwp_default_post_type'] : 'post';
        $defaultPostStatus = isset($settings['genwp_default_post_status']) ? $settings['genwp_default_post_status'] : 'publish';

        $authorId = $defaultAuthor;
        $termId = null; 
    
        if ($keywordData) {
            $authorId = $keywordData['user_id'];
            $term = get_term($keywordData['term_id']);
    
            if (!is_wp_error($term)) {
                $termId = $term->term_id;
            }
        }
        
        $slug = sanitize_title($keyword);
        
        $postArr = [
            'post_title'    => wp_strip_all_tags($title),
            'post_content'  => $content,
            'post_status'   => $defaultPostStatus,
            'post_type'     => $defaultPostType,
            'post_author'   => $authorId,
            'post_name'     => $slug,
        ];
        
        $postId = wp_insert_post($postArr);
        
        if (!is_wp_error($postId) && $termId) {
            wp_set_object_terms($postId, $termId, 'category');
        }
        
        return $postId;
    }
}