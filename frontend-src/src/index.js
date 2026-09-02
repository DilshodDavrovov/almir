import React from "react";
import ReactDOM from "react-dom";
import App from "./App";
import { BrowserRouter } from 'react-router-dom';
import {Provider} from 'react-redux';
import {store} from './store/store';
import  ThemeContext  from "./context/ThemeContext";

ReactDOM.render(
    <Provider store = {store}>
            <BrowserRouter basename='/'>
                <ThemeContext>
                    <App />
                </ThemeContext>  
            </BrowserRouter>    
    </Provider>,
  document.getElementById("root")
);
