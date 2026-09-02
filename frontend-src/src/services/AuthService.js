import axios from 'axios';
import {
    logout,
} from '../store/actions/AuthActions';
import { baseURL } from "./AxiosInstance";
const url = `${baseURL}/api/v2.0`;
export default {
    signUp(data) {
        return axios.post(`${url}/register`, data);
    },
    login(data) {
        return axios.post(`${url}/login`,data);
    },
    saveTokenInSessionStorage(token) {
        sessionStorage.setItem('token', token);
    },
    checkAutoLogin(dispatch, history) {
        const tokenDetailsString = sessionStorage.getItem('token');
        if (!tokenDetailsString) {
            dispatch(logout(history));
            return;
        }
    }
}