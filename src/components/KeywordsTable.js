import React, { useState, useEffect } from 'react';

import API from '../services/api';
import useErrorHandler from '../hooks/useErrorHandler';
import useNotification from '../hooks/useNotification';
import Pagination from './GenPagination';
import '../style/genwp.css';
import { KeywordRow } from './KeywordRow';
import useFetchData from '../hooks/useFetchData';

const KeywordsTable = () => {
  const [selectedKeywords, setSelectedKeywords] = useState([]);
  const [editingKeywordId, setEditingKeywordId] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [selectedUser, setSelectedUser] = useState({});
  const [selectedCategory, setSelectedCategory] = useState({});
  const [error, handleError, clearError] = useErrorHandler();
  const [notification, showNotification, clearNotification] = useNotification();
  const [selectAllChecked, setSelectAllChecked] = useState(false);
  const [mappedKeywords, setMappedKeywords] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(10);

  const [selectedUserForAll, setSelectedUserForAll] = useState('');
  const [selectedCategoryForAll, setSelectedCategoryForAll] = useState('');


  const {
    keywords,
    setKeywords,
    users,
    categories,
    loading,
    totalItems,
    fetchData,
  } = useFetchData(currentPage, itemsPerPage);
  
  
  const editKeyword = (keywordId, newKeywordValue) => {
    setKeywords(
      keywords.map(kw => (kw.id === keywordId ? { ...kw, editedKeyword: newKeywordValue } : kw))
    );
  }; 

  // Save the edited keyword
  const saveEditedKeyword = async (keywordId) => {
    const keyword = keywords.find(kw => kw.id === keywordId);
    const oldKeyword = keyword.keyword;
    const newKeyword = keyword.editedKeyword;

    try {
      const response = await API.updateKeyword(oldKeyword, newKeyword);
  
      if (response.data.success) {
        showNotification('Keyword updated successfully.');
        setEditingKeywordId(null);
        fetchData();
      } else {
        handleError(response.data.message || 'An error occurred while updating the keyword.');
      }
    } catch (error) {
      console.error('An error occurred while updating the keyword:', error);
      if (error.response && error.response.data && error.response.data.message) {
        handleError(error.response.data.message);
      } else {
        showNotification('An unexpected error occurred while updating the keyword.');
      }
    }
  }; 

  // Function to map the keyword
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
      setMappedKeywords(prev => [...prev, keyword]);
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

  const keywordsDelete = async () => {
    setDeleting(true); 
    try {
      const response = await API.deleteKeywords(selectedKeywords);
      if (response.data.success) {
        showNotification('Keywords deleted successfully.');
        // Filter out the deleted keywords from the state
        const remainingKeywords = keywords.filter(kw => !selectedKeywords.includes(kw.keyword));
        setKeywords(remainingKeywords);
        setSelectedKeywords([]); 
      } else {
        handleError(response.data.message || 'An error occurred while deleting keywords.');
      }
    } catch (error) {
      handleError(error.response.data.message || 'An error occurred while deleting keywords.');
    }
    setDeleting(false);
  };  

  const WriteArticles = async () => {
    const response = await API.writeArticles(selectedKeywords);

    if (response.data.success) {
      showNotification('Articles scheduled successfully.');
    } else {
      handleError(response.data.message || 'An error occurred while Scheduling  articles.');
    }
    fetchData();
  };  

  const handlePageChange = (newPage) => {
    if (newPage > 0 && newPage <= Math.ceil(totalItems / itemsPerPage)) {
      setCurrentPage(newPage);
      fetchData(newPage, itemsPerPage);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault(); 

    // we will add more here
  };

  const mapAllSelectedKeywords = async () => {
    try {
      const mappingPromises = selectedKeywords.map(keyword => 
        mapKeyword(keyword, selectedUserForAll, selectedCategoryForAll)
      );
      
      await Promise.all(mappingPromises);
  
    } catch(error) {
      console.error('An error occurred while mapping all keywords:', error);
    }
  };
  
  

  return (
    <div className="container bg-gray-50 mx-auto p-4 space-y-4 w-full">
      <div className="mb-4">
      {error && (
        <div className="bg-red-500 text-white p-2 rounded relative">
          {error}
          <button 
            className="absolute top-0 right-0 p-2 hover:bg-red-600 rounded-bl" 
            onClick={clearError}
          >
            ✖
          </button>
        </div>
      )}

        {notification && (
          <div className="bg-blue-500 text-white p-2 rounded mt-2 relative">
            {notification}
            <button 
              className="absolute top-0 right-0 p-2 hover:bg-blue-600 rounded-bl" 
              onClick={clearNotification}
            >
              ✖
            </button>
          </div>
        )}

      </div>
  
      {loading ? (
        <div className="text-center text-gray-600">Loading Keywords...</div>
      ) : keywords.length === 0 ? (
        <div className="text-center text-gray-600">No Keywords Found. Please Upload Some</div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="overflow-x-auto">
            <table className="w-full divide-y divide-gray-200">
            <thead className="bg-matte-black">
              <tr>
                <th className="px-4 py-2 text-white">
                <input
                  type="checkbox"
                  className="form-checkbox"
                  checked={selectAllChecked}
                  onChange={() => {
                    setSelectAllChecked(!selectAllChecked);
                    setSelectedKeywords(selectAllChecked ? [] : keywords.map(keyword => keyword.keyword));
                  }}
                />
                </th>
                <th className="px-4 py-2 text-white">Keyword</th>
                <th className="px-4 py-2 text-white">User</th>
                <th className="px-4 py-2 text-white">Category</th>
                <th className="px-4 py-2 text-white">Actions</th>
                <th className="px-4 py-2 text-white">Keyword Mapping</th>
              </tr>
            </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {keywords.map((keyword) => (
                  <KeywordRow 
                    key={keyword.id}
                    keyword={keyword} 
                    categories={categories}
                    users={users}
                    editKeyword={editKeyword}
                    saveEditedKeyword={saveEditedKeyword}
                    mapKeyword={mapKeyword}
                    editingKeywordId={editingKeywordId}
                    setEditingKeywordId={setEditingKeywordId}
                    selectedUser={selectedUser}
                    setSelectedUser={setSelectedUser}
                    selectedCategory={selectedCategory}
                    setSelectedCategory={setSelectedCategory}
                    selectedKeywords={selectedKeywords}
                    setSelectedKeywords={setSelectedKeywords}
                    mappedKeywords={mappedKeywords}
                  />
                ))}
              </tbody>
            </table>
          </div>

          <div className="bulk-actions flex flex-col md:flex-row items-start md:items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
              <div className="flex flex-col space-y-2">
                <select 
                  className="p-2 border rounded w-full md:w-auto"
                  onChange={(e) => setSelectedUserForAll(e.target.value)}
                >
                  {users.map(user => (
                    <option key={user.id} value={user.id}>
                      {user.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="flex flex-col space-y-2">
                <select 
                  className="p-2 border rounded w-full md:w-auto"
                  onChange={(e) => setSelectedCategoryForAll(e.target.value)}
                >
                  {categories.map(category => (
                    <option key={category.term_id} value={category.term_id}>
                      {category.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="flex items-center">
                <button 
                  className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded"
                  onClick={mapAllSelectedKeywords}
                >
                  Map All Keywords
                </button>
              </div>
          </div>

          <div className="border-t mt-4 mb-4"></div>
  
          <div className="flex flex-col sm:flex-row justify-between space-y-2 sm:space-y-0 sm:space-x-4">
            <button type="button" onClick={WriteArticles} className="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
              Write Articles
            </button>            
            
            <button type="button" onClick={keywordsDelete} className="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
              Delete Keywords
            </button>
            {deleting && <div className="text-red-600">Deleting...</div>}
          </div>
           
          <Pagination
           totalItems={totalItems} 
           itemsPerPage={itemsPerPage} 
           currentPage={currentPage} 
           onPageChange={handlePageChange}
           className="mt-4"
          />
        </form>
      )}
    </div>
  );  
};

export default KeywordsTable;