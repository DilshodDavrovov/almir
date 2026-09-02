import { Crud } from './api';
import axiosInstance from "../AxiosInstance";
const crud = new Crud('/dt');
export default {
    table : "dt",
    save : (data) => crud.save(data),
    select: (is_active, is_deleted, search ='') => crud.select(is_active, is_deleted, search),
    getIdsList: (idList) => crud.getIdsList(idList),
    getAllList: () => axiosInstance.post(`/dt/all?page=${1}`, {
        is_active: true,
        deleted: false,
        limit: 200
    }),
    getList : (page, limit, is_active, deleted, dataID, {key, value}) => crud.getList(page, limit, is_active, deleted, dataID, {key, value}),
    getById: (id) => crud.getById(id),
    softDelete : (id) =>  crud.softDelete(id),
    edit : (id, data) => crud.edit(id, data),
    changeStatus: (id, data) => crud.changeStatus(id, data),
    updateSelected: (data) => crud.updateSelected(data),
    deleteSelected: (data) => crud.deleteSelected(data),
    uploadFile: (data) => crud.uploadFile(data)
};