import axiosInstance from "./AxiosInstance";

export default {
    getActivity: (table) => {
        if(table){
            return axiosInstance.get(`/user/activity?filter=${table}`)
        } else {
            return axiosInstance.get(`/user/activity`)
        }
    }
}
