import { Crud } from './api';
const crud = new Crud('/dist');
export default {
    name : "dist",
    table: "dist",
    filter : (page, data) => crud.filter('/filter/dist', page, data),
    filterById : (page, data) => crud.filterById('/filter/get/dist', page, data),
    save : (data) => crud.save(data),
    select: (is_active, is_deleted, search, additional) => crud.select(is_active, is_deleted, search, additional),
    getIdsList: (idList) => crud.getIdsList(idList),
    getList : (page, limit, is_active, deleted, dataID, {key, value}) => crud.getList(page, limit, is_active, deleted, dataID, {key, value}),
    getById: (id) => crud.getById(id),
    softDelete : (id) =>  crud.softDelete(id),
    edit : (id, data) => crud.edit(id, data),
    changeStatus: (id, data) => crud.changeStatus(id, data),
    updateSelected: (data) => crud.updateSelected(data),
    deleteSelected: (data) => crud.deleteSelected(data),
    uploadFile: (data) => crud.uploadFile(data)
};