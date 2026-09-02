export const USER_EDIT_DATA_ACTION = 'user_edit_data_action'

export function userEditDataAction(data){
    return{
        type: USER_EDIT_DATA_ACTION,
        payload: data
    }
}
