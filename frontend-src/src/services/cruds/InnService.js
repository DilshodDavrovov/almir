import { Crud } from './api';
const crud = new Crud('/inn');
export default {
    name : "inn",
    table: "inn",
    filter : (page, data) => crud.filter('/filter/inns', page, data),
    filterById : (page, data) => crud.filterById('/filter/get/inns', page, data),
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