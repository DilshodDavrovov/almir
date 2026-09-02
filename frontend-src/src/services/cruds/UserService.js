import axios from "axios";
import axiosInstance from "../AxiosInstance";

const api = '/user';

export default {
    save : data => {
        return axiosInstance.post(`${api}/add`, data)
    },
    myInfo : () => {
        return axiosInstance.get(`${api}/my/info`)
    },
    getOne: (id)=>{
        return axiosInstance.get(`${api}/get/${id}`)
    },
    getList: (page, limit, is_active, deleted, is_blocked, {key, value}, filter_data) => axiosInstance.post(`${api}/all?page=${page}`, {
        sortBy: key || null,
        sortByDesc: value,
        ...filter_data,
        "is_active": is_active,
        "is_deleted": deleted,
        "is_blocked": is_blocked,
        "limit": limit
    }),
    changeStatus: (id,data) =>{
        return axiosInstance.put(`${api}/status/${id}`, data)
    },
    softDelete: (id) =>{
        return axiosInstance.put(`${api}/delete/${id}`)
    },
    edit: (data) =>{
        return axiosInstance.put(`${api}/update/info`, data)
    },
    changePass: (user_id, password) =>{
        return axiosInstance.put(`${api}/update/password`, {user_id, password})
    },
    changeAccess: (user_id, data) =>{
        return axiosInstance.put(`${api}/update/access/${user_id}`, data)
    },
    uploadFile: (data) =>{
        return axiosInstance.post(`${api}/bulk/import`, data, {
            headers: {
                'Content-Type': 'application/json'
            }
        })
    },
    uploadImage: (data) =>{
        return axiosInstance.post(`${api}/update/avatar`, data,{
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    },
    getMacAddress: () => {
        return axios.post(`http://localhost:3366/get/mac`)
    }
}
