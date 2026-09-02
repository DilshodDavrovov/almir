import axiosInstance from "./AxiosInstance";
const api = "/system/settings-info";

export default {
    sendMessage: (data) => axiosInstance.post(`/contact/support`, data),
    getSetting: () => axiosInstance.get(`/contact/info`),
    setSetting: (data) => axiosInstance.post(`${api}`, data)
};
