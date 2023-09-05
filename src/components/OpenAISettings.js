import React, { useState, useEffect, useCallback } from 'react';
import { saveToLocalStorage, fetchFromLocalStorage } from '../utils/utils';
import API from '../services/api';

import APIKeyField from './APIKeyField';

const OpenAISettings = () => {
    const [settings, setSettings] = useState({
        'genwp-openai-api-key': '',
        'model': '',
        'max_tokens': '',
        'temperature': '',
        'top_p': '',
        'frequency_penalty': '',
        'presence_penalty': '',
        'pexels_api_key': ''
    });
      
    const [showOpenAIKey, setShowOpenAIKey] = useState(false);
    const [showPexelsKey, setShowPexelsKey] = useState(false);
    const [status, setStatus] = useState('idle');

    // Fetch the current settings from WP
    const fetchSettings = useCallback(async () => {
        try {
            let loadedSettings = fetchFromLocalStorage('genwp-settings');

            if (!loadedSettings) {
                loadedSettings = await API.fetchSettings();
                saveToLocalStorage('genwp-settings', loadedSettings);
            }
    
            const completeSettings = {
                'genwp-openai-api-key': '',
                'model': '',
                'max_tokens': '',
                'temperature': '',
                'top_p': '',
                'frequency_penalty': '',
                'presence_penalty': '',
                'pexels_api_key': '',
                ...loadedSettings
            };

            // Replace undefined values with defaults
            Object.keys(completeSettings).forEach(key => {
                if (completeSettings[key] === undefined) {
                    completeSettings[key] = '';
                }
            });
    
            setSettings(completeSettings);
        } catch (error) {
            console.error('Error fetching settings:', error);
        }
    }, []);

    // Load FetchSettings 
    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    // Models
    const models = [
        { value: 'gpt-4', label: 'GPT-4' },
        { value: 'gpt-3.5-turbo-16k', label: 'GPT-3.5 Turbo 16K' },
        { value: 'text-davinci-003', label: 'Davinci' }
    ];

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
            await API.saveSettings(settings);

            saveToLocalStorage('genwp-settings', settings);
            setStatus('saved');
            fetchSettings();
        } catch (error) {
            setStatus('failed'); 
            console.error('Error saving settings:', error);
        }
    };

    const getButtonText = () => {
        switch (status) {
            case 'saving': return 'Saving...';
            case 'saved': return 'Save Settings';
            default: return 'Save Settings';
        }
    };

    const toggleOpenAIKeyVisibility = () => {
        setShowOpenAIKey(!showOpenAIKey);
    };
    
    const togglePexelsKeyVisibility = () => {
        setShowPexelsKey(!showPexelsKey);
    };
    
    const apiKeyValue = (keyName) => {
        if (keyName === 'genwp-openai-api-key') return showOpenAIKey ? settings[keyName] : '•'.repeat(32);
        if (keyName === 'pexels_api_key') return showPexelsKey ? settings[keyName] : '•'.repeat(32);
        return '';
    };

    return (
        <div className="wrap p-8 bg-gray-50 rounded shadow-lg w-full">
            <h2 className="text-2xl font-bold mb-4">OpenAI Settings</h2>
            {status === 'saved' && (
                <div className="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
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
            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">OpenAI API Key</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2"> This is your unique API key for making requests to OpenAI's services. You can get an API key by logging in to the <a className="text-blue-500" href="https://beta.openai.com/account/api-keys" target="_blank">OpenAI website</a>.</p>
                        <APIKeyField keyType="genwp-openai" />
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">OpenAI Model</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">The choice of the language model to utilize. Each model has distinct capabilities and performance metrics.</p>
                    <div className="w-full sm:w-96">
                        <select name="model" value={settings.model} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            {models.map(model => (
                                <option value={model.value} key={model.value}>{model.label}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">Maximum Tokens</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">Set the maximum number of tokens (words/characters) for the model's output.</p>
                    <div className="w-full sm:w-96">
                        <input type="number" name="max_tokens" value={settings.max_tokens} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">Temperature</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">Determines the randomness of the output. Higher values produce more random outputs, while lower values produce more consistent, focused outputs.</p>
                    <div className="w-full sm:w-96">
                        <input type="number" step="0.01" min="0" max="1" name="temperature" value={settings.temperature} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">Top P</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">Represents the nucleus sampling. It controls the diversity of the response.</p>
                    <div className="w-full sm:w-96">
                        <input type="number" step="0.01" min="0" max="1" name="top_p" value={settings.top_p} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">Frequency Penalty</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">Adjusts preference for using frequent or infrequent tokens in the response.</p>
                    <div className="w-full sm:w-96">
                        <input type="number" step="0.1" min="-2" max="2" name="frequency_penalty" value={settings.frequency_penalty} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <label className="font-medium mb-2">Presence Penalty</label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">Controls the introduction of new concepts in the model's output.</p>
                    <div className="w-full sm:w-96">
                        <input type="number" step="0.1" min="-2" max="2" name="presence_penalty" value={settings.presence_penalty} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4 border-b border-gray-200 pb-4">
                    <h2 className="text-2xl font-bold mb-4">Pexel Settings</h2>
                    <label className="font-medium mb-2">Pexels API Key</label>
                        <p className="text-xs sm:text-sm text-gray-600 mb-2">
                        This is your unique API key for making requests to Pexels' services. You can get an API key by logging in to the <a className="text-blue-500" href="https://www.pexels.com/api/new/" target="_blank">Pexels website</a>.
                        </p>
                        <APIKeyField keyType="pexels" />
                </div>
                <div>
                    <input type="submit" value={getButtonText()} className="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded cursor-pointer" />
                </div>
            </form>
        </div>
    );    
};

export default OpenAISettings;