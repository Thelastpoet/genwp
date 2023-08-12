import React, { useState } from 'react';
import OpenAISettings from './OpenAISettings';
import ArticleSettings from './ArticleSettings';
import UploadKeywords from './UploadKeywords';
import KeywordsTable from './KeywordsTable';
import Documentation from './Docs';

const Settings = () => {
    const [activeTab, setActiveTab] = useState('openai');
    const settings = {
        'genwp-openai-api-key': '',
        'model': '',
        'max_tokens': '',
        'temperature': '',
        'frequency_penalty': '',
        'presence_penalty': '',
        'pexels-api-key': ''
    };

    return (
        <React.Fragment>
            <h2 className="text-2xl font-bold mb-4">GenWP Settings</h2>
            <div className="flex flex-col md:flex-row">
                <div className="flex-1 md:pr-4 mb-4 md:mb-0">
                    <div className="flex flex-col md:flex-row bg-matte-black mb-4">
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
                <div className="w-full md:w-1/4 p-4 md:border-l">
                    <Documentation activeTab={activeTab} />
                </div>
            </div>
        </React.Fragment>
    );    
};

export default Settings;