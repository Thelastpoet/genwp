import axios from 'axios';

const baseAPI = axios.create({
    baseURL: '/wp-json/genwp/v1/'
});

const API = {
    getKeywords: (page, itemsPerPage) => baseAPI.get(`keywords?page=${page}&per_page=${itemsPerPage}`),
    getUsers: () => baseAPI.get('authors'),
    getCategories: () => baseAPI.get('taxonomy-terms/category'),
    updateKeyword: (oldKeyword, newKeyword) => baseAPI.post('keywords/update', { oldKeyword, newKeyword }),
    mapKeyword: (keyword, userId, termId) => baseAPI.post('keywords/mapping', { keyword, userId, termId }),
    deleteKeywords: (keywordsToDelete) => baseAPI.post('keywords/delete', { keywords: keywordsToDelete }),
    writeArticles: (selectedKeywords) => baseAPI.post('keywords/write-articles', { keywords: selectedKeywords }),
};

export default API;