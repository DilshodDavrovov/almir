import axios from 'axios';
import { store } from '../store/store';
// REACT_APP_API_URL=origin -> API served from the same host as the SPA (nginx routes /api, /public).
// PUBLIC_URL is the deployment sub-path ('' at the root, '/almir' behind a reverse proxy), so the
// same build works in both cases: the API and the /public file links keep the prefix.
const envApiUrl = process.env.REACT_APP_API_URL;
const publicPath = (process.env.PUBLIC_URL || '').replace(/\/$/, '');
export const baseURL = envApiUrl === 'origin' ? window.location.origin + publicPath : (envApiUrl || 'https://api2.almir.uz')
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
                // hard redirect: must carry the deployment sub-path, it bypasses the router basename
                window.location.replace(publicPath + "/login");
            }
        } catch(err){
            return Promise.reject(error);
        }
     
        return Promise.reject(error);
    },
);

export default axiosInstance;
