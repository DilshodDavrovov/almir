import axiosInstance from "./AxiosInstance";
const api = "/stat";

export default {
    getTotal : (fromDate, toDate) => axiosInstance.post(`/filter/period-data`, {
        is_active: true,
        deleted: false,
        filterByDate: [{fromDate,toDate}]
    }),
    getTopsList: (byTableList, fromDate, toDate) => axiosInstance.post(`${api}/period-data-list`, {
        is_active: true,
        deleted: false,
        limit: 5,
        byTableList,
        fromDate,
        toDate,
    }),
    getTops: (byTable, fromDate, toDate) => axiosInstance.post(`${api}/period-data`, {
        is_active: true,
        deleted: false,
        limit: 5,
        byTable,
        fromDate,
        toDate,
    }),
    getCount: () => axiosInstance.post(`${api}/data-counts`, {
        is_active: true,
        deleted: false,
    }),

    getUpdateLog: () => axiosInstance.get(`/log/update/info`),
    updateLog: (date) => axiosInstance.post(`/log/update/edit`, {
        "title": "Последнее обновление",
        "updated_date": date,
        "is_active": true,
        "is_deleted": false
    }),

};
