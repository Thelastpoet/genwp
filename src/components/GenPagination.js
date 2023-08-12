import React from 'react';
import PropTypes from 'prop-types';

const Pagination = ({ totalItems, itemsPerPage, currentPage, onPageChange }) => {
  const validatedTotalItems = Number(totalItems) || 0;
  const validatedItemsPerPage = Number(itemsPerPage) || 1;

  const totalPages = Math.ceil(validatedTotalItems / validatedItemsPerPage);

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
        Page {currentPage}
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

Pagination.propTypes = {
  totalItems: PropTypes.number.isRequired,
  itemsPerPage: PropTypes.number.isRequired,
  currentPage: PropTypes.number.isRequired,
  onPageChange: PropTypes.func.isRequired
};

export default Pagination;