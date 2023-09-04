import { useState, useEffect, useCallback } from 'react';
import API from '../services/api';

const useFetchData = (currentPage, itemsPerPage) => {
  const [keywords, setKeywords] = useState([]);
  const [users, setUsers] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);
  const [error, setError] = useState(null);
  
    const fetchData = useCallback(async () => {
      setLoading(true);
      setError(null);
      try {
        const keywordsData = await API.getKeywords(currentPage, itemsPerPage);
        const usersData = await API.getUsers();
        const categoriesData = await API.getCategories();

        setKeywords(keywordsData.data.keywords || []);
        setUsers(usersData.data);
        setCategories(categoriesData.data);
        setTotalItems(keywordsData.data.total || 1000);
      } catch (error) {
        console.error('An error occurred while fetching data:', error);
        setError(error);
      }
      setLoading(false);
    }, [currentPage, itemsPerPage]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

  return {
    keywords,
    setKeywords,
    users,
    categories,
    loading,
    totalItems,
    fetchData,
    error
  };
};

export default useFetchData;
