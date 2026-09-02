import {
    USER_EDIT_DATA_ACTION, 
} from '../actions/UserAction';

const initialState = {
    editData:  {
        id: null,
        email: null,
        first_name: null,
        last_name: null,
        middle_name: null,
        phone_number: null,
        passport_info: null,
        user_role: null,
        address: null,
        company_name: null,
        company_inn: null,
        um_created_at: new Date(),
        um_expired_at: new Date(),
        otm_created_at: new Date(),
        user_mac: null,
        one_time_mac: null,
        confirmed: false,
        is_blocked: false,
        status: false,
    }
};

export function UserReducer(state = initialState, action) {
    if (action.type === USER_EDIT_DATA_ACTION) {
        return {
            ...state,
            editData: {...action.payload}
        };
    }
    return state;
}

    
