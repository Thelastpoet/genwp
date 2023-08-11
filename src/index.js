import React from 'react';
import ReactDOM from 'react-dom';
import App from './App';
import './styles.css';

document.addEventListener( 'DOMContentLoaded', function() {
    var element = document.getElementById( 'genwp-admin' );
    if ( typeof element !== 'undefined' && element !== null ) {
        const root = ReactDOM.createRoot(element);
        root.render(<App />);
    } 
})