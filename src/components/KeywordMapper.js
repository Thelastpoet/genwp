import React from 'react';
import API from '../services/api';
import useErrorHandler from '../hooks/useErrorHandler';
import useNotification from '../hooks/useNotification';

const mapKeyword = async (keyword, userId, termId) => {
    // Check if both userId and termId are selected
    if (!userId || !termId) {
      showNotification("Please select both a user and a category before mapping."); 
      return; 
    }
  
    const response = await KeywordMapping(keyword, userId, termId);
    if (response) {
      // Update the keyword's user and category in the local state
      const updatedKeywords = keywords.map(kw => 
        kw.keyword === keyword ? { ...kw, userId: userId, categoryId: termId } : kw
      );
      setKeywords(updatedKeywords);
    }
};

const KeywordMapping = async (keyword, userId, termId) => {
    try {
      const response = await API.mapKeyword(keyword, userId, termId);
      if (response.data.success) {
        showNotification('Keyword mapping updated successfully.');
        fetchData(); 
        return response.data;
      } else {
        handleError(response.data.message || 'An error occurred while updating the keyword mapping.');
        return null;
      }
    } catch (error) {
      console.error('An error occurred while mapping the keyword:', error);
      handleError('An error occurred while mapping the keyword.');
      return null;
    }
};



export default KeywordMapping;