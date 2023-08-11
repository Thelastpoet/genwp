import { useState } from 'react';

const useErrorHandler = () => {
  const [error, setError] = useState(null);

  const handleError = (err, defaultMessage = 'An unexpected error occurred.') => {
    const errorMessage = err?.response?.data?.message || defaultMessage;
    setError(errorMessage);
    console.error(errorMessage, err);
  };

  const clearError = () => {
    setError(null);
  };

  return [error, handleError, clearError];
};

export default useErrorHandler;