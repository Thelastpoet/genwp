import React, { useState, useEffect } from 'react';

import API from '../services/api';
import { getItem, setItem } from '../utils/LocalStorage';

const ArticleSettings = (props) => {
    const defaultSettings = {
        genwp_default_author: '',
        genwp_default_post_type: '',
        genwp_default_post_status: '',
        genwp_cron_frequency: '',
    };
    const [settings, setSettings] = useState(() => {
        const localData = getItem('genwpArticleSettings');
        if (localData) {
            return { ...localData };
        }
        if (props.settings && Object.keys(props.settings).length) {
            return { ...props.settings };
        }
        return { ...defaultSettings };
    });
        
    const [status, setStatus] = useState('idle');
    const [authors, setAuthors] = useState([]);
    const [postTypes, setPostTypes] = useState([]);
    const [postStatuses, setPostStatuses] = useState([]);
    const [error, setError] = useState(null);

    // Fetch the current settings from WP
    const fetchSettings = async () => {
        try {
            const response = await API.fetchArticleSettings();    
            setSettings({ ...defaultSettings, ...response.data });
        } catch (error) {
            setError('Failed to fetch settings.');
            console.error('Error fetching settings:', error);
        }
    };    

    // Fetch the current settings from WP
    const fetchData = async (apiFunc, setDataFunc, signal) => {
        try {
            const response = await apiFunc({ signal });
            setDataFunc(response.data);
        } catch (error) {
            console.error(`Error fetching data: ${error}`);
        }
    };
    
    useEffect(() => {
        const abortController = new AbortController();
        const signal = abortController.signal;

        fetchData(API.fetchAuthors, setAuthors,  signal);
        fetchData(API.fetchPostTypes, setPostTypes, signal);
        fetchData(API.fetchPostStatuses, setPostStatuses, signal);
        fetchSettings();        
        
        return () => {
            abortController.abort();
        };
    }, []);    

    useEffect(() => {
        setItem('genwpArticleSettings', settings);
    }, [settings]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSettings(prevSettings => ({
            ...prevSettings,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setStatus('saving');

        try {
            await API.saveArticleSettings(settings);
            setStatus('saved');
            fetchSettings();
        } catch (error) {
            setStatus('failed'); 
            console.error('Error saving settings:', error);
        }
    };

    const renderFields = () => {
        return fields.map((field) => {
            const commonInputClasses = "border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none";
            switch (field.type) {
                case 'dropdown_users':
                    return (
                        <div key={field.name} className="flex flex-col mb-4">
                        <label className="font-medium mb-2">{field.label}</label>
                        <select name={field.name} value={settings[field.name]} onChange={handleChange} className={commonInputClasses}>
                            {authors.map((author) => (
                            <option key={author.id} value={author.id}>
                                {author.name}
                            </option>
                            ))}
                        </select>
                        </div>
                    );

                case 'dropdown_post_types':
                    return (
                        <div key={field.name} className="flex flex-col mb-4">
                        <label className="font-medium mb-2">{field.label}</label>
                        <select name={field.name} value={settings[field.name]} onChange={handleChange} className={commonInputClasses}>
                            {postTypes.map((type, key) => (
                            <option key={key} value={type}>
                                {type}
                            </option>
                            ))}
                        </select>
                        </div>
                    );

                    case 'dropdown_post_statuses':
                        return (
                          <div key={field.name} className="flex flex-col mb-4">
                            <label className="font-medium mb-2">{field.label}</label>
                            <select name={field.name} value={settings[field.name]} onChange={handleChange} className={commonInputClasses}>
                              {postStatuses.map((status, key) => (
                                <option key={key} value={status}>
                                  {status}
                                </option>
                              ))}
                            </select>
                          </div>
                        );
                      

                case 'number':
                    return (
                        <div key={field.name} className="flex flex-col mb-4 pb-4">
                            <label className="font-medium mb-2">{field.label}</label>
                            <input
                                type="number"
                                name={field.name}
                                value={settings[field.name]}
                                onChange={handleChange}
                                min="1"
                                max="24"
                                className={`${commonInputClasses} w-96`}
                            />
                            {field.name === 'genwp_cron_frequency' && (
                                <p className="text-xs sm:text-sm text-gray-600 mb-2">
                                    Enter a value from 1 to 24. This is the number of times the articles will be generated per day.
                                </p>
                            )}
                        </div>
                    );
                default:
                    return (
                        <div key={field.name} className="flex flex-col mb-4">
                            <label className="font-medium mb-2">{field.label}</label>
                            <input type="text" name={field.name} value={settings[field.name]} onChange={handleChange} className={commonInputClasses} />
                        </div>
                    );
            }
        });
    };

    const fields = [
        { name: 'genwp_default_author', label: 'Default Post Author', type: 'dropdown_users' },
        { name: 'genwp_default_post_type', label: 'Default Post Type', type: 'dropdown_post_types' },
        { name: 'genwp_default_post_status', label: 'Default Post Status', type: 'dropdown_post_statuses' },
        { name: 'genwp_cron_frequency', label: 'Articles Per Day', type: 'number' },
    ];
    
    return (
        <div className="wrap p-8 bg-gray-50 rounded shadow-lg w-full">
            {error && (
                <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong className="font-bold">Error!</strong>
                    <span className="block sm:inline"> {error}</span>
                </div>
            )}

            {status === 'saved' && (
                <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong className="font-bold">Success!</strong>
                    <span className="block sm:inline"> Settings have been saved.</span>
                </div>
            )}
            {status === 'failed' && (
                <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong className="font-bold">Failed!</strong>
                    <span className="block sm:inline"> Failed to save settings. Please try again.</span>
                </div>
            )}
            <h2 className="text-2xl font-semibold mb-4">Article Settings</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
                {renderFields()}
                <div>
                    <input type="submit" value={status === 'saving' ? 'Saving...' : 'Save Settings'} className="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded cursor-pointer" />
                </div>
            </form>
        </div>

    );
    
};

export default ArticleSettings;