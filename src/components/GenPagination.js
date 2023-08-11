import React from 'react';

const Pagination = ({ currentPage, itemsPerPage, totalItems, onPageChange }) => {
  const totalPages = Math.ceil(totalItems / itemsPerPage);

  return (
    <div className="flex items-center justify-between mt-4">
      <button 
        onClick={() => onPageChange(currentPage - 1)} 
        disabled={currentPage === 1}
        className={`bg-blue-500 text-white px-4 py-2 rounded-l-lg focus:outline-none ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'}`}
      >
        Previous
      </button>
      <span className="mx-4 text-gray-700">
        Page {currentPage} of {totalPages}
      </span>
      <button 
        onClick={() => onPageChange(currentPage + 1)} 
        disabled={currentPage === totalPages}
        className={`bg-blue-500 text-white px-4 py-2 rounded-r-lg focus:outline-none ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'}`}
      >
        Next
      </button>
    </div>
  );
};

export default Pagination;