import axios from 'axios';
import { store } from '../store/store';
// REACT_APP_API_URL=origin -> API served from the same host/port as the SPA (nginx routes /api, /public)
const envApiUrl = process.env.REACT_APP_API_URL;
export const baseURL = envApiUrl === 'origin' ? window.location.origin : (envApiUrl || 'https://api2.almir.uz')
const axiosInstance = axios.create({
    baseURL: `${baseURL}/api/v2.0`
});

axiosInstance.interceptors.request.use(
    request => {
        request.headers.Authorization = 'Bearer ' + sessionStorage.getItem("token");
        return request;
    },
    error => error,
    config => {
        const state = store.getState();
        const token = state.auth.auth.idToken;
        return config;
    }
);

axiosInstance.interceptors.response.use(
    response =>  response,
    error => {
        try {
            if(error.response.status === 401){
                sessionStorage.removeItem("token");
                window.location.replace("/login");
            }
        } catch(err){
            return Promise.reject(error);
        }
     
        return Promise.reject(error);
    },
);

export default axiosInstance;
