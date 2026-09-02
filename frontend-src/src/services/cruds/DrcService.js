import axiosInstance from "../AxiosInstance";

const api = '/drc';

export default {
    name: 'drc',
    save: data => {
        return axiosInstance.post(`${api}/add`, data)
    },
    getCurrencyList: (data) => {
        return axiosInstance.post(`${api}/converter`, data)
    },
    getOne: (id, data_type) => {
        return axiosInstance.get(`${api}/get/${id}?data_type=${data_type}`)
    },
    getList: (page, limit, is_active, is_deleted, dataID, { key, value }, periodCode, productTypeIds, dateRange, drugCode, data_type) => axiosInstance.post(`${api}/all?page=${page}`, {
        data_type,
        is_active,
        is_deleted,
        sortBy: key || null,
        sortByDesc: value,
        dtID: productTypeIds,
        drugCode: drugCode ? [drugCode] : [],
        limit,
        dataID,
        periodCode,
        ...dateRange,
    }),
    delete: (id, data) => {
        return axiosInstance.put(`${api}/switch/${id}`, data)
    },
    softDelete: (id) => {
        return axiosInstance.delete(`${api}/delete/${id}`);
    },
    edit: (id, data) => {
        return axiosInstance.put(`${api}/update/${id}`, data)
    },
    changeStatus: (id, data) => {
        return axiosInstance.put(`${api}/status/${id}`, data)
    },
    deleteSelected: (data) => {
        return axiosInstance.delete(`${api}/bulk/remove`, { data: data })
    },
    updateSelected: (data) => {
        return axiosInstance.put(`${api}/bulk/status`, data)
    },
    uploadFile: (data, setUploadLoading) => axiosInstance.post(`${api}/bulk/import`, data, {
        onUploadProgress: e => {
            if (e.loaded === e.total) {
                setUploadLoading(1);
            }
        },
        timeout: 6000 * 1000,
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
    search: (text) => axiosInstance.get(`/drug/find?search=${text}`)
};
