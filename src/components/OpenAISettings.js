import React, { useState, useEffect } from 'react';
import axios from 'axios';

const OpenAISettings = (props) => {
    const [settings, setSettings] = useState(props.settings);
    const [showOpenAIKey, setShowOpenAIKey] = useState(false);
    const [showPexelsKey, setShowPexelsKey] = useState(false);
    const [status, setStatus] = useState('idle');

    // Fetch the current settings from WP or from local storage if available
    const fetchSettings = async () => {
        try {
            let loadedSettings;
            const cachedSettings = localStorage.getItem('genwp-settings');
            if (cachedSettings) {
                loadedSettings = JSON.parse(cachedSettings);
            } else {
                const url = '/wp-json/genwp/v1/settings';
                const response = await axios.get(url);
                loadedSettings = response.data;
                localStorage.setItem('genwp-settings', JSON.stringify(loadedSettings));
            }
            setSettings(loadedSettings);
        } catch (error) {
            console.error('Error fetching settings:', error);
        }
    };

    // Load FetchSettings 
    useEffect(() => {
        fetchSettings();
    }, []);

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
            const url = '/wp-json/genwp/v1/settings';
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': genwpLocal.nonce
                }
            };

            await axios.post(url, settings, config);
            localStorage.setItem('genwp-settings', JSON.stringify(settings));
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
        <div className="wrap p-8 bg-white rounded shadow-lg w-full">
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
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">OpenAI API Key</label>
                    <div className="flex items-center">
                        <div className="w-96">
                        <input type="text" name="genwp-openai-api-key" value={apiKeyValue('genwp-openai-api-key')} onChange={handleChange} readOnly={!showOpenAIKey} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                        </div>
                        <button type="button" onClick={toggleOpenAIKeyVisibility} className="ml-2 px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded cursor-pointer">
                            {showOpenAIKey ? 'Hide Key' : 'Show Key'}
                        </button>
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">OpenAI Model</label>
                    <div className="w-96">
                        <select name="model" value={settings.model} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            {models.map(model => (
                                <option value={model.value} key={model.value}>{model.label}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">Maximum Tokens</label>
                    <div className="w-96">
                        <input type="number" name="max_tokens" value={settings.max_tokens} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">Temperature</label>
                    <div className="w-96">
                        <input type="number" step="0.01" min="0" max="1" name="temperature" value={settings.temperature} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">Top P</label>
                    <div className="w-96">
                        <input type="number" step="0.01" min="0" max="1" name="top_p" value={settings.top_p} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">Frequency Penalty</label>
                    <div className="w-96">
                        <input type="number" step="0.1" min="-2" max="2" name="frequency_penalty" value={settings.frequency_penalty} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">Presence Penalty</label>
                    <div className="w-96">
                        <input type="number" step="0.1" min="-2" max="2" name="presence_penalty" value={settings.presence_penalty} onChange={handleChange} className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div className="flex flex-col mb-4">
                    <label className="font-medium mb-2">Pexels API Key</label>
                    <div className="flex items-center">
                        <div className="w-96">
                            <input type="text" name="pexels_api_key" value={apiKeyValue('pexels_api_key')} onChange={handleChange} readOnly={!showPexelsKey} placeholder="Enter your API key" className="border p-2 rounded w-full focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                        </div>
                        <button type="button" onClick={togglePexelsKeyVisibility} className="ml-2 px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded cursor-pointer">
                            {showPexelsKey ? 'Hide Key' : 'Show Key'}
                        </button>
                    </div>
                </div>
                <div>
                    <input type="submit" value={getButtonText()} className="px-4 py-2 bg-blue-500 text-white hover:bg-blue-600 rounded cursor-pointer" />
                </div>
            </form>
        </div>
    );    
};

export default OpenAISettings;
