import {
    LOGOUT_ACTION,
} from '../actions/AuthActions';

const initialState = {
    auth: '',
    errorMessage: '',
    successMessage: ''
};

export function AuthReducer(state = initialState, action) {

    if (action.type === LOGOUT_ACTION) {
        return {
            ...state,
            auth: '' ,
            errorMessage: '',
            successMessage: '',
        };
    }

    return state;
}

    
