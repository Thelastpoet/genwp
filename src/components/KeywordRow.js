import React from 'react';

export const KeywordRow = ({ 
  keyword, 
  categories, 
  users, 
  editKeyword, 
  saveEditedKeyword, 
  mapKeyword, 
  editingKeywordId, 
  setEditingKeywordId, 
  selectedUser, 
  setSelectedUser, 
  selectedCategory, 
  setSelectedCategory, 
  selectedKeywords, 
  setSelectedKeywords, 
  mappedKeywords 
}) => {

  const isEditing = editingKeywordId === keyword.id;

  return (
    <tr key={keyword.id} className="text-gray-700">
      <td className="px-6 py-4">
        <input
          type="checkbox"
          name="keywords[]"
          value={keyword.keyword}
          checked={selectedKeywords.includes(keyword.keyword)}
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
          readOnly={!isEditing}
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
        {isEditing ? (
          <>
            <button onClick={() => saveEditedKeyword(keyword.id)} className="w-full sm:w-auto bg-blue-500 hover:bg-black text-white px-4 py-2 rounded mr-2">Save</button>
            <button onClick={() => setEditingKeywordId(null)} className="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Cancel</button>
          </>
        ) : (
          <button onClick={() => setEditingKeywordId(keyword.id)} className="w-full sm:w-auto bg-blue-500 hover:bg-black text-white px-4 py-2 rounded">Edit Keyword</button>
        )}
      </td>
      <td className="px-6 py-4">
        <button
          type="button"
          onClick={() => mapKeyword(keyword.keyword, selectedUser[keyword.id], selectedCategory[keyword.id])}
          className="w-full sm:w-auto bg-black hover:bg-blue-600 text-white px-4 py-2 rounded"
        >
          {mappedKeywords.includes(keyword.keyword) ? 'Mapped' : 'Map Keyword'}
        </button>
      </td>
    </tr>
  );
};
