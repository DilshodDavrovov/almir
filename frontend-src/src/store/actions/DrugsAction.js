export const DRUG_ADD_DATA_ACTION = 'drug_add_data_action';
export const DRUG_ADD_SELECT_ACTION = 'drug_add_select_action';
export const DRUG_ADD_SELECT_LOADING_ACTION = 'drug_add_select_loading_action';
export const DRUG_EDIT_DATA_ACTION = 'drug_edit_data_action';
export const DRUG_EDIT_SELECT_ACTION = 'drug_edit_select_action';
export const DRUG_EDIT_SELECT_LOADING_ACTION = 'drug_edit_select_loading_action';

export function drugAddDataAction(data) {
    return {
        type: DRUG_ADD_DATA_ACTION,
        payload: data,
    };
}
export function drugAddSelectAction(data) {
    return {
        type: DRUG_ADD_SELECT_ACTION,
        payload: data,
    };
}
export function drugAddSelectLoadingAction(data) {
    return {
        type: DRUG_ADD_SELECT_LOADING_ACTION,
        payload: data,
    };
}
export function drugEditDataAction(data) {
    return {
        type: DRUG_EDIT_DATA_ACTION,
        payload: data,
    };
}
export function drugEditSelectAction(data) {
    return {
        type: DRUG_EDIT_SELECT_ACTION,
        payload: data,
    };
}
export function drugEditSelectLoadingAction(data) {
    return {
        type: DRUG_EDIT_SELECT_LOADING_ACTION,
        payload: data,
    };
}