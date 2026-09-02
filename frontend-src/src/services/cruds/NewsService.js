import axiosInstance from '../AxiosInstance';
const api = '/news';

export default {
    save : data => axiosInstance.post(`${api}/add`, data),
    edit : (id,data) => {
        return axiosInstance.post(`${api}/update/${id}`,data)
    },
    select: (search, is_active = true, is_deleted = false) => {
        return axiosInstance.get(`${api}?search=${search}&is_active=${is_active}&is_deleted=${is_deleted}`);
    },
    getIdsList: (idList) => {
        return axiosInstance.get(`${api}?idList=${idList}`);
    },
    getForHome : () => axiosInstance.post(`${api}/all?page=1`, {
        is_active: true,
        deleted: false,
        limit: 4
    }),
    getList : (page, limit, is_active, deleted, dataID, {key, value}) => axiosInstance.post(`${api}/all?page=${page}`, {
        is_active,
        deleted,
        sortBy: key || null,
        sortByDesc: value,
        limit,
        dataID
    }),
    getById: (id) => axiosInstance.get(`${api}/get/${id}`),
    softDelete : (id) => {
        return axiosInstance.delete(`${api}/delete/${id}`);
    },
    changeStatus: (id, data) => {
        return axiosInstance.put(`${api}/status/${id}`, data)
    },
    deleteSelected: (data) => {
        return axiosInstance.delete(`${api}/bulk/remove`, {data: data})
    },
    updateSelected: (data) => {
        return axiosInstance.put(`${api}/bulk/status`, data)
    }
};