import React from "react";
import ReactDOM from "react-dom";
import App from "./App";
import { BrowserRouter } from 'react-router-dom';
import {Provider} from 'react-redux';
import {store} from './store/store';
import  ThemeContext  from "./context/ThemeContext";
// Design layer (tokens, layout, components). Imported last on purpose: it must be
// the final stylesheet in the bundle so it overrides the vendor template.
import "./jsx/theme.css";

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
