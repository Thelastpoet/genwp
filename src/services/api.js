import axios from 'axios';

const baseAPI = axios.create({
    baseURL: '/wp-json/genwp/v1/'
});

// Default headers for POST requests
baseAPI.interceptors.request.use(config => {
    if (config.method === 'post') {
        config.headers['X-WP-Nonce'] = genwpLocal.nonce;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

const API = {
    // KeywordsTable.js APIs
    getKeywords: (page = 1, limit = 10) => baseAPI.get(`keywords`, {
        params: {
            page: page,
            limit: limit
        }
    }),
    getUsers: () => baseAPI.get('authors'),
    getCategories: () => baseAPI.get('taxonomy-terms/category'),
    updateKeyword: (oldKeyword, newKeyword) => baseAPI.post('keywords/update', { oldKeyword, newKeyword }),
    mapKeyword: (keyword, userId, termId) => baseAPI.post('keywords/mapping', { keyword, userId, termId }),
    deleteKeywords: (keywordsToDelete) => baseAPI.post('keywords/delete', { keywords: keywordsToDelete }),
    writeArticles: (selectedKeywords) => baseAPI.post('keywords/write-articles', { keywords: selectedKeywords }),

    // ArticleSettings.js APIs
    fetchArticleSettings: () => baseAPI.get('article-settings'),
    fetchAuthors: () => baseAPI.get('authors'),
    fetchPostTypes: () => baseAPI.get('types'),
    fetchPostStatuses: () => baseAPI.get('statuses'),
    saveArticleSettings: (settings, nonce) => baseAPI.post('article-settings', settings, {
        headers: {
            'Content-Type': 'application/json'
        }
    }),

    // APIKeyField.js APIs
    getAPIKey: (keyType) => baseAPI.get(`get-${keyType}-api-key`),
    saveAPIKey: (keyType, apiKey) => baseAPI.post(`${keyType}-api-key`, { key: apiKey }),
};

export default API;