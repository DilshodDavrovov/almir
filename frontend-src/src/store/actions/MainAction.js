export const MAC_ADDRESS = 'mac_address';
export const LAST_UPDATE_DATE = 'last_update_date';
export const SETTINGS_DATA = 'settings_data';
export const PRODUCT_TYPES = 'product_types';
export const USER_INFO = 'user_info';
export const ANALYZE_ACTIVITY = 'analyze_activity';

export function setMacAddress(data){
    return {
        type: MAC_ADDRESS,
        payload: data.slice(data.indexOf(":") + 1),
    }
}

export function setLastDate(data){
    return {
        type: LAST_UPDATE_DATE,
        payload: data,
    }
}

export function setUserInfo(data){
    return {
        type: USER_INFO,
        payload: {...data},
    }
}

export function setAnalyzeActivity(data){
    return{
        type: ANALYZE_ACTIVITY,
        payload: [...data]
    }
}

export function setSettingsData(data){
    return{
        type: SETTINGS_DATA,
        payload: {...data}
    }
}
export function setProductTypes(data){
    return{
        type: PRODUCT_TYPES,
        payload: [...data]
    }
}