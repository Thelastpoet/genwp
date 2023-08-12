import React, { useState } from 'react';
import axios from 'axios';

const UploadKeywords = () => {
  const [file, setFile] = useState(null);
  const [errorMessage, setErrorMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccessful, setIsSuccessful] = useState(false);

  const handleFileChange = (e) => {
    setFile(e.target.files[0]);
  };

  const handleFormSubmit = async (e) => {
    e.preventDefault();
    if (!file) {
      setErrorMessage('Please select a CSV file.');
      return;
    }
  
    setIsLoading(true);
    setErrorMessage('');
    const formData = new FormData();
    formData.append('file', file);
  
    try {
      const response = await axios.post('/wp-json/genwp/v1/upload-keywords', formData, {
        headers: {
          'X-WP-Nonce': genwpLocal.nonce,
        },
      });

      setIsLoading(false);
  
      if (response.data.success) {
        setIsSuccessful(true);
      } else {
        // Handle server-side error messages here
        setErrorMessage(response.data.message || 'An error occurred while uploading the CSV file.');
      }
    } catch (error) {
      setIsLoading(false);
      setErrorMessage('An error occurred while uploading the file.');
    }
  };  
 
  return (
    <div className="wrap genwp-wrap p-8 bg-gray-50 flex flex-col items-center rounded shadow-lg w-full">
      <h1 className="genwp-main-title text-2xl font-bold mb-6 text-center">Upload Keywords</h1>
      {errorMessage && (
        <div className="notice notice-error bg-red-200 p-4 text-red-600 mb-4 w-full max-w-md">
          <p>Error uploading keywords: {errorMessage}</p>
        </div>
      )}
      {isSuccessful && (
        <div className="notice notice-success bg-green-200 p-4 text-green-600 mb-4 w-full max-w-md">
          <p>Keywords uploaded successfully!</p>
        </div>
      )}
      {isLoading ? (
        <div className="genwp-loading text-blue-600 mb-4">Uploading...</div> // Add a proper loader here
      ) : (
        <form method="post" onSubmit={handleFormSubmit} className="genwp-form w-full max-w-md">
          <div className="genwp-section genwp-section-upload mb-4 border-dashed border-2 border-gray-500 p-8 rounded-lg text-center">
            <label htmlFor="genwp-keyword-file" className="block mb-2 text-xl">Upload CSV:</label>
            <input
              type="file"
              id="genwp-keyword-file"
              name="genwp_keyword_file"
              accept=".csv"
              onChange={handleFileChange}
              className="p-2 border rounded mb-2 w-full"
            />
            <p className="description text-gray-600 mb-4">Please upload a CSV file with one keyword per line.</p>
            <button type="submit" className="bg-blue-600 text-white px-4 py-2 rounded-lg genwp-upload-button">
              Upload CSV
            </button>
          </div>
        </form>
      )}
    </div>
  );  
};

export default UploadKeywords;