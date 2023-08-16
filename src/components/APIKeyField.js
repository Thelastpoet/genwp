import React, { useState } from 'react';
import API from '../services/api';

function APIKeyField({ keyType }) {
    const [editing, setEditing] = useState(false);
    const [apiKey, setApiKey] = useState('');
    const [initialApiKey, setInitialApiKey] = useState('');
    const [message, setMessage] = useState(''); 

    const saveAPIKey = async () => {
        if (apiKey === initialApiKey) {
            setEditing(false);
            return;
        }
        if (!apiKey) {
            setMessage('Please enter an API Key');
            return;
        }
        if (apiKey.length < 50) {
            setMessage('API Key must be at least 50 characters');
            return;
        }
        if (apiKey.length > 60) {
            setMessage('API Key must be less than 56 characters');
            return;
        }        
        try {
            setMessage('Saving API Key...');
            const response = await API.saveAPIKey(keyType, apiKey);

            if (response.data.success) {
                setInitialApiKey(apiKey)
                setMessage('');
                setEditing(false);
            } else {
                setMessage('Error saving API key! Try Again');
            }
        } catch (error) {
            setMessage(`Error: ${error.message}`);
        }
    };

    const toggleEdit = () => {
        if (!editing) {
            fetchAPIKey();
        }
        setEditing(prevEditing => !prevEditing);
    };

    const fetchAPIKey = async () => {
        try {
            setMessage('Retrieving API Key...');
            const response = await API.getAPIKey(keyType);

            const retrievedKey = response.data.key || '';
            setApiKey(retrievedKey);
            setInitialApiKey(retrievedKey);

            if (!retrievedKey) {
                setMessage( 'Please set your API key.' );
                setEditing(true);
            } else {
                setMessage('');
                setEditing(true);
            }
        } catch (error) {
            setMessage(`Error: ${error.message}`);
        }
    };

    const handleChange = (event) => {
        setApiKey(event.target.value);
    };
    
    return (
        <div className="flex flex-col sm:flex-row items-center">
            <div className="w-full sm:w-96 mr-0 sm:mr-4">
                <input 
                    type="text" 
                    name={`genwp-${keyType}-api-key`} 
                    value={message || (editing ? apiKey : 'API Key Saved')}
                    onChange={handleChange} 
                    readOnly={!editing} 
                    className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" 
                />
            </div>
            <button 
                type="button" 
                onClick={editing ? saveAPIKey : toggleEdit} 
                className="mt-2 sm:mt-0 w-full sm:w-auto px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded cursor-pointer"
            >
                {editing ? 'Save' : 'View API Key'}
            </button>
        </div>
    );
    
}

export default APIKeyField;