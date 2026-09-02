import axiosInstance from "../AxiosInstance";

export class Crud {

    static api;
    constructor(api) {
        this.api = api;
    }

    save = (data) => axiosInstance.post(`${this.api}/add`, data);
    uploadFile = (data) => axiosInstance.post(`${this.api}/bulk/import`, data, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    });
    getList = (page, limit, is_active, deleted, dataID, { key, value }) => axiosInstance.post(`${this.api}/all?page=${page}`, {
        is_active,
        deleted,
        sortBy: key || null,
        sortByDesc: value,
        limit,
        dataID
    });
    getById = (id) => axiosInstance.get(`${this.api}/get/${id}`);
    getIdsList = (idList) => axiosInstance.get(`${this.api}?idList=${idList}`);
    select = (is_active, is_deleted, search, additional) => {
        let query = '';
        if (additional) {
            Object.keys(additional).forEach(key => {
                if (additional[key]) {
                    if (Array.isArray(additional[key])) {
                        if (additional[key].length) {
                            query += `&${key}=${additional[key].join(',')}`
                        }
                    } else {
                        query += `&${key}=${additional[key]}`
                    }
                }
            })
        }
        return axiosInstance.get(`${this.api}?search=${search}&is_active=${is_active ? 1 : 0}&is_deleted=${is_deleted ? 1 : 0}${query}`);
    }
    edit = (id, data) => axiosInstance.put(`${this.api}/update/${id}`, data);
    changeStatus = (id, data) => axiosInstance.put(`${this.api}/status/${id}`, data);
    updateSelected = (data) => axiosInstance.put(`${this.api}/bulk/status`, data)
    softDelete = (id) => axiosInstance.delete(`${this.api}/delete/${id}`);
    deleteSelected = (data) => axiosInstance.delete(`${this.api}/bulk/remove`, { data: data });
    filter = (api, page, data) => axiosInstance.post(`${api}?page=${page}`, data);
    filterById = (api, page, data) => axiosInstance.post(`${api}?page=${page}`, data);
}