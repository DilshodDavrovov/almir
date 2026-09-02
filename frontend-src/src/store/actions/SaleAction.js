export const SALE_TABLE_PRICE = 'sale_table_price';
export const SALE_TABLE_C_PRICE = 'sale_table_c_price';
export const SALE_TABLE_SUM_PRICE = "sale_table_sum_price";
export const SALE_ADD_DATA_ACTION = 'sale_add_data_action';
export const SALE_ADD_SELECT_ACTION = 'sale_add_select_action';
export const SALE_ADD_SELECT_LOADING_ACTION = 'sale_add_select_loading_action';
export const SALE_EDIT_DATA_ACTION = 'sale_edit_data_action';
export const SALE_EDIT_SELECT_ACTION = 'sale_edit_select_action';
export const SALE_EDIT_SELECT_LOADING_ACTION = 'sale_edit_select_loading_action';
export const SALE_CONVERT_PRICE_LOADING_ACTION = 'sale_convert_price_loading_action';
export const SALE_CONVERT_C_PRICE_LOADING_ACTION = 'sale_convert_c_price_loading_action';

export function saleTablePriceAction(data) {
    return {
        type: SALE_TABLE_PRICE,
        payload: data,
    };
}
export function saleTableCPriceAction(data) {
    return {
        type: SALE_TABLE_C_PRICE,
        payload: data,
    };
}
export function saleTableSumPriceAction(data) {
    return {
        type: SALE_TABLE_SUM_PRICE,
        payload: data,
    };
}

export function saleAddDataAction(data) {
    return {
        type: SALE_ADD_DATA_ACTION,
        payload: data,
    };
}
export function saleAddSelectAction(data) {
    return {
        type: SALE_ADD_SELECT_ACTION,
        payload: data,
    };
}
export function saleAddSelectLoadingAction(data) {
    return {
        type: SALE_ADD_SELECT_LOADING_ACTION,
        payload: data,
    };
}
export function saleEditDataAction(data) {
    return {
        type: SALE_EDIT_DATA_ACTION,
        payload: data,
    };
}
export function saleEditSelectAction(data) {
    return {
        type: SALE_EDIT_SELECT_ACTION,
        payload: data,
    };
}
export function saleEditSelectLoadingAction(data) {
    return {
        type: SALE_EDIT_SELECT_LOADING_ACTION,
        payload: data,
    };
}
export function priceLoadingAction(data, key){
    if(key === 'price'){
        return {
            type: SALE_CONVERT_PRICE_LOADING_ACTION,
            payload: data,
        };
    } else if(key === 'c_price'){
        return {
            type: SALE_CONVERT_C_PRICE_LOADING_ACTION,
            payload: data,
        };
    }
}
