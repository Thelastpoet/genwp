<?php

namespace GenWP;

use WP_REST_Request;
use WP_REST_Response;

class Gen_Key_Table {
    private $keywords = array();
    private $db;
    private $users;
    private $categories;

    public function __construct() {
        $this->db = new  genWP_Db();
        $this->keywords = $this->db->get_keywords();
        $this->users = get_users();
        $this->categories = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
    }

    public function get_keywords_data(WP_REST_Request $request) {
        $page = $request->get_param('page') ? intval($request->get_param('page')) : 1;
        $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 10;
    
        $offset = ($page - 1) * $limit;
    
        // Retrieve a subset of keywords based on limit and offset
        $paged_keywords = array_slice($this->keywords, $offset, $limit);
    
        if (empty($paged_keywords)) {
            return new WP_REST_Response(['success' => false, 'message' => 'No keywords found for the given page'], 404);
        }
    
        return new WP_REST_Response([
            'success' => true,
            'keywords' => $paged_keywords,
            'users' => $this->users,
            'categories' => $this->categories,
            'current_page' => $page,
            'total_keywords' => count($this->keywords),
            'total_pages' => ceil(count($this->keywords) / $limit)
        ], 200);
    }
    

    public function update_keyword_mapping(WP_REST_Request $request) {
        $keyword = $request->get_param('keyword');
        $userId = $request->get_param('userId');
        $termId = $request->get_param('termId');

        $result = $this->db->update_keyword_mapping($keyword, $userId, $termId);
        if ($result === false) {
            return new WP_REST_Response(['success' => false, 'message' => 'Failed to update keyword mapping'], 500);
        }

        $this->keywords = $this->db->get_keywords();
        return new WP_REST_Response(['success' => true, 'keyword' => $keyword, 'userId' => $userId, 'termId' => $termId], 200);
    }

    public function delete_keywords(WP_REST_Request $request) {        
        $keywordsToDelete = $request->get_param('keywords');

        $result = $this->db->delete_keywords($keywordsToDelete);
        if ($result === false) {
            return new WP_REST_Response(['success' => false, 'message' => 'Failed to delete keywords'], 500);
        }

        $this->keywords = $this->db->get_keywords();
        return new WP_REST_Response(['success' => true], 200);
    }

    public function write_articles(WP_REST_Request $request) {  
        $selectedKeywords = $request->get_param('keywords');
    
        $current_keywords = get_option('genwp_selected_keywords', []);
        $merged_keywords = array_unique(array_merge($current_keywords, $selectedKeywords));
        $result = update_option('genwp_selected_keywords', $merged_keywords);
        if ($result === false) {
            return new WP_REST_Response(['success' => false, 'message' => 'Failed to update selected keywords'], 500);
        }

        return new WP_REST_Response(['success' => true], 200);
    }    

    public function update_keyword(WP_REST_Request $request) {       
        $oldKeyword = $request->get_param('oldKeyword');
        $newKeyword = $request->get_param('newKeyword');

        error_log("Updating keyword: Old Keyword = $oldKeyword, New Keyword = $newKeyword");

        $result = $this->db->update_keyword($oldKeyword, $newKeyword);
        if ($result === false) {
            return new WP_REST_Response(['success' => false, 'message' => 'Failed to update keyword'], 500);
        }

        $this->keywords = $this->db->get_keywords();
        return new WP_REST_Response(['success' => true], 200);
    }
}