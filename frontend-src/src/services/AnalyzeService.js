import axiosInstance from "./AxiosInstance";

export default {
    filterTotal : (data) => axiosInstance.post(`/filter/period-data`, data),
    clearRedisCache: () => axiosInstance.get('/clear-redis-data')
};
