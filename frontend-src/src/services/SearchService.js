import axiosInstance from "./AxiosInstance";
const api = "/search/advanced";

export default {
    filterGroup: (page, data) => axiosInstance.post(`${api}/group?page=${page}`, data),
    filter : (page, data) => axiosInstance.post(`${api}?page=${page}`, data),
    filterTotal : (data) => axiosInstance.post(`${api}/common`, data),
    filterById : (page, data) => axiosInstance.post(`${api}/get/full?page=${page}`, data),
    newFilter : (page, data) => axiosInstance.post(`${api}/filter?page=${page}`, data),
    newFilterById : (page, data) => axiosInstance.post(`${api}/filter/get?page=${page}`, data),
    graphReport : (data) => axiosInstance.post(`/graph/filter`, data),



};
