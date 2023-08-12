const Documentation = ({ activeTab }) => {
    const documentationContent = {
        openai: 'Here, you can configure settings related to OpenAI. Enter your API key, select a model, and adjust other parameters.',
        article: 'This tab allows you to configure default settings for your articles. You can set default authors, post types, and more.',
        uploadKeywords: 'You can upload keywords in this tab. Keywords can be used to generate custom content.',
        keywordsTable: 'View and manage your keywords here. You can edit, delete, or add new keywords as needed.'
    };

    return (
        <div className="p-4 bg-gray-50 rounded shadow-md border-t-2">
            <h2 className="font-bold mb-4">Documentation:</h2>
            <p>{documentationContent[activeTab]}</p>
        </div>
    );
};

export default Documentation;
