import axiosInstance from "../AxiosInstance";
import { Crud } from './api';

const api = '/drug';
const crud = new Crud(api);
export default {
    name : "drug",
    table: "drug",
    filter : (page, data) => crud.filter('/filter/drugs', page, data),
    filterById : (page, data) => crud.filterById('/filter/get/drugs', page, data),
    save : (data) => crud.save(data),
    select: (is_active, is_deleted, search, additional) => crud.select(is_active, is_deleted, search, additional),
    getIdsList: (idList) => crud.getIdsList(idList),
    getList : (page, limit, is_active, deleted, dataID, {key, value}, dataCode, productTypeIds) => axiosInstance.post(`${api}/all?page=${page}`, {
        is_active,
        deleted,
        sortBy: key || null,
        sortByDesc: value,
        dtID: productTypeIds,
        limit,
        dataID,
        dataCode
    }),
    getById: (id) => crud.getById(id),
    softDelete : (id) =>  crud.softDelete(id),
    edit : (id, data) => crud.edit(id, data),
    changeStatus: (id, data) => crud.changeStatus(id, data),
    updateSelected: (data) => crud.updateSelected(data),
    deleteSelected: (data) => crud.deleteSelected(data),

    uploadFile: (data, setUploadLoading) => axiosInstance.post(`${api}/bulk/import`, data,  {
        onUploadProgress: e => {
            if(e.loaded === e.total){
                setUploadLoading(1);
            }
        },
        timeout: 6000 * 1000,
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
    search: (search, additional) => {
        let query = '';
        if(additional){
            Object.keys(additional).forEach(key => {
                if(additional[key] && additional[key].length)
                query += `&${key}=${additional[key].join(',')}`
            })   
        }
        return axiosInstance.get(`${api}/find?search=${search}${query}`);
    },
};