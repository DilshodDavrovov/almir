export const DRC_TABLE_PRICE = 'drc_table_price';
export const DRC_TABLE_C_PRICE = 'drc_table_c_price';
export const DRC_TABLE_SUM_PRICE = "drc_table_sum_price";
export const DRC_ADD_DATA_ACTION = 'drc_add_data_action';
export const DRC_ADD_SELECT_ACTION = 'drc_add_select_action';
export const DRC_ADD_SELECT_LOADING_ACTION = 'drc_add_select_loading_action';
export const DRC_EDIT_DATA_ACTION = 'drc_edit_data_action';
export const DRC_EDIT_SELECT_ACTION = 'drc_edit_select_action';
export const DRC_EDIT_SELECT_LOADING_ACTION = 'drc_edit_select_loading_action';
export const DRC_CONVERT_PRICE_LOADING_ACTION = 'drc_convert_price_loading_action';
export const DRC_CONVERT_C_PRICE_LOADING_ACTION = 'drc_convert_c_price_loading_action';

export function drcTablePriceAction(data) {
    return {
        type: DRC_TABLE_PRICE,
        payload: data,
    };
}
export function drcTableCPriceAction(data) {
    return {
        type: DRC_TABLE_C_PRICE,
        payload: data,
    };
}
export function drcTableSumPriceAction(data) {
    return {
        type: DRC_TABLE_SUM_PRICE,
        payload: data,
    };
}

export function drcAddDataAction(data) {
    return {
        type: DRC_ADD_DATA_ACTION,
        payload: data,
    };
}
export function drcAddSelectAction(data) {
    return {
        type: DRC_ADD_SELECT_ACTION,
        payload: data,
    };
}
export function drcAddSelectLoadingAction(data) {
    return {
        type: DRC_ADD_SELECT_LOADING_ACTION,
        payload: data,
    };
}
export function drcEditDataAction(data) {
    return {
        type: DRC_EDIT_DATA_ACTION,
        payload: data,
    };
}
export function drcEditSelectAction(data) {
    return {
        type: DRC_EDIT_SELECT_ACTION,
        payload: data,
    };
}
export function drcEditSelectLoadingAction(data) {
    return {
        type: DRC_EDIT_SELECT_LOADING_ACTION,
        payload: data,
    };
}
export function priceLoadingAction(data, key){
    if(key === 'price'){
        return {
            type: DRC_CONVERT_PRICE_LOADING_ACTION,
            payload: data,
        };
    } else if(key === 'c_price'){
        return {
            type: DRC_CONVERT_C_PRICE_LOADING_ACTION,
            payload: data,
        };
    }
}
