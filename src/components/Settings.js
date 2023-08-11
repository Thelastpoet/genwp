import React, { useState } from 'react';
import OpenAISettings from './OpenAISettings';
import ArticleSettings from './ArticleSettings';
import UploadKeywords from './UploadKeywords';
import KeywordsTable from './GenKeyTable';

const Settings = () => {
    const [activeTab, setActiveTab] = useState('openai');
    const settings = {
        'genwp-openai-api-key': '',
        'model': '',
        'max_tokens': '',
        'temperature': '',
        'frequency_penalty': '',
        'presence_penalty': '',
        'pexels_api_key': ''
    };

    return (
        <React.Fragment>
            <h2 className="text-2xl font-bold mb-4">GenWP Settings</h2>
            <div className="flex">
                <div className="flex-1 pr-4">
                    <div className="flex bg-matte-black mb-4">
                        <button className={`px-4 py-2 flex-1 ${activeTab === 'openai' ? 'bg-blue-500 text-white' : 'bg-matte-black text-white hover:bg-blue-500'}`} onClick={() => setActiveTab('openai')}>OpenAI Settings</button>
                        <button className={`px-4 py-2 flex-1 ${activeTab === 'article' ? 'bg-blue-500 text-white' : 'bg-matte-black text-white hover:bg-blue-500'}`} onClick={() => setActiveTab('article')}>Article Settings</button>
                        <button className={`px-4 py-2 flex-1 ${activeTab === 'uploadKeywords' ? 'bg-blue-500 text-white' : 'bg-matte-black text-white hover:bg-blue-500'}`} onClick={() => setActiveTab('uploadKeywords')}>Upload Keywords</button>
                        <button className={`px-4 py-2 flex-1 ${activeTab === 'keywordsTable' ? 'bg-blue-500 text-white' : 'bg-matte-black text-white hover:bg-blue-500'}`} onClick={() => setActiveTab('keywordsTable')}>Keywords</button>
                    </div>
                    <div className="settings-content">
                        {activeTab === 'openai' && <OpenAISettings settings={settings} />}
                        {activeTab === 'article' && <ArticleSettings settings={settings} />}
                        {activeTab === 'uploadKeywords' && <UploadKeywords />}
                        {activeTab === 'keywordsTable' && <KeywordsTable />}
                    </div>
                </div>
                <div className="w-1/4 p-4 border-l">
                    <p>Placeholder text for the sidebar.</p>
                </div>
            </div>
        </React.Fragment>
    );
};

export default Settings;
