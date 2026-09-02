import { formatDateToDay } from '../../utils';
import {
    ANALYZE_ACTIVITY,
    USER_INFO,
    MAC_ADDRESS,
    SETTINGS_DATA,
    LAST_UPDATE_DATE,
    PRODUCT_TYPES
} from '../actions/MainAction'
const initialState = {
    userInfo: {},
    settingsData:{},
    productTypes: [],
    macAddress: '',
    lastUpdateDate: formatDateToDay(new Date()),
    analyzeActivity: [],
};

export function MainReducer(state = initialState, action) {
    if (action.type === USER_INFO) {
        return {
            ...state,
            userInfo: {...action.payload}
        };
    }
    if (action.type === MAC_ADDRESS) {
        return {
            ...state,
            macAddress: action.payload
        };
    }
    if (action.type === LAST_UPDATE_DATE) {
        return {
            ...state,
            lastUpdateDate: action.payload
        };
    }

    if(action.type === ANALYZE_ACTIVITY){
        return {
            ...state,
            analyzeActivity: [...action.payload]
        }
    }

    if(action.type === SETTINGS_DATA){
        return {
            ...state,
            settingsData: {...action.payload}
        }
    }
    if(action.type === PRODUCT_TYPES){
        return {
            ...state,
            productTypes: [...action.payload]
        }
    }
    return state;
}

    
