import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';

import API from '../services/api';
import useErrorHandler from '../hooks/useErrorHandler';
import useNotification from '../hooks/useNotification';
import Pagination from './GenPagination';
import '../style/genwp.css';

const KeywordsTable = () => {
  const [keywords, setKeywords] = useState([]);
  const [users, setUsers] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedKeywords, setSelectedKeywords] = useState([]);
  const [editingKeywordId, setEditingKeywordId] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [selectedUser, setSelectedUser] = useState({});
  const [selectedCategory, setSelectedCategory] = useState({});  
  const [error, handleError, clearError] = useErrorHandler();
  const [notification, showNotification, clearNotification] = useNotification();
  const [selectAllChecked, setSelectAllChecked] = useState(false);

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(20);
  const [totalItems, setTotalItems] = useState(0);
  
  useEffect(() => {
    let isMounted = true;
    fetchData();
    return () => { isMounted = false; };
  }, [currentPage, itemsPerPage]);

  const fetchData = async () => {
    setLoading(true);
    try {
      const keywordsData = await API.getKeywords(currentPage, itemsPerPage);
      const usersData = await API.getUsers();
      const categoriesData = await API.getCategories();
      
      ReactDOM.unstable_batchedUpdates(() => {
          setKeywords(keywordsData.data.keywords || []);
          setUsers(usersData.data);
          setCategories(categoriesData.data);
          setTotalItems(keywordsData.data.total || 0);
          setLoading(false);
      });
    } catch (error) {
      console.error('An error occurred while fetching data:', error);
    }
    setLoading(false);
  };
  
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
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault(); 

    // we will add more here
  };

  return (
    <div className="container mx-auto p-4 space-y-4 w-full">
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
                  <tr key={keyword.id} className="text-gray-700">
                    <td className="px-6 py-4">
                      <input
                        type="checkbox"
                        name="keywords[]"
                        value={keyword.keyword}
                        checked={selectAllChecked || selectedKeywords.includes(keyword.keyword)}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedKeywords([...selectedKeywords, keyword.keyword]);
                          } else {
                            setSelectedKeywords(selectedKeywords.filter(k => k !== keyword.keyword));
                          }
                        }}
                        className="form-checkbox"
                      />
                    </td>
                    <td className="px-6 py-4">
                      <input
                        type="text"
                        className="form-input w-full"
                        value={keyword.editedKeyword || keyword.keyword || ''}
                        readOnly={editingKeywordId !== keyword.id}
                        onChange={e => editKeyword(keyword.id, e.target.value)}
                      />
                    </td>
                    <td className="px-6 py-4">
                      <select 
                        value={selectedUser[keyword.id] || ''}
                        onChange={e => setSelectedUser(prev => ({ ...prev, [keyword.id]: e.target.value }))}
                        className="form-select w-full"
                      >
                        {users.map(user => (
                          <option key={user.id} value={user.id}>
                            {user.name}
                          </option>
                        ))}
                      </select>
                    </td>
                    <td className="px-6 py-4">
                      <select
                        value={selectedCategory[keyword.id] || ''}
                        onChange={e => setSelectedCategory(prev => ({ ...prev, [keyword.id]: e.target.value }))}
                        className="form-select w-full"
                      >
                        {categories.map(category => (
                          <option key={category.term_id} value={category.term_id}>
                            {category.name}
                          </option>
                        ))}
                      </select>
                    </td>
                    <td className="px-6 py-4">
                    {editingKeywordId === keyword.id ? (
                      <>
                        <button onClick={() => saveEditedKeyword(keyword.id)} className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2">Save</button>
                        <button onClick={() => setEditingKeywordId(null)} className="bg-red-400 hover:bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                      </>
                    ) : (
                      <button onClick={() => setEditingKeywordId(keyword.id)} className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Edit Keyword</button>
                    )}
                    </td>
                    <td className="px-6 py-4">
                      <button
                        type="button"
                        onClick={() => mapKeyword(keyword.keyword, selectedUser[keyword.id], selectedCategory[keyword.id])}
                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                      >
                        Map Keyword
                      </button>
                    </td>
                  </tr>
                ))}
            </tbody>
          </table>
  
          <div className="flex justify-between space-x-4">
            <button type="button" onClick={WriteArticles} className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
              Write Articles
            </button>
            <button type="button" onClick={keywordsDelete} className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
              Delete Keywords
            </button>
            {deleting && <div className="text-gray-600">Deleting...</div>}
          </div>
  
          <Pagination
            currentPage={currentPage}
            itemsPerPage={itemsPerPage}
            totalItems={totalItems}
            onPageChange={handlePageChange}
            className="mt-4"
          />
        </form>
      )}
    </div>
  );  
};

export default KeywordsTable;