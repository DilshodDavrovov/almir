import {
    DRUG_ADD_DATA_ACTION, DRUG_ADD_SELECT_ACTION, DRUG_ADD_SELECT_LOADING_ACTION, DRUG_EDIT_DATA_ACTION, DRUG_EDIT_SELECT_ACTION, DRUG_EDIT_SELECT_LOADING_ACTION
} from '../actions/DrugsAction';

const initialState = {
    data: {
        dataCode: "",
        name: "",
        ref_price: 1,
        ref_price_ccy: "UZS",
        di_id: "",
        df_id: "",
        dfg_id: "",
        dtg_id: "",
        dt_id: "",
        trademark_id: "",
        is_rx: false,
        is_otc: false,
        is_active: true,
        deleted: false,
        manufacturers: [{ manufacturer_id: "", drug_mxik: "" }]
    },
    list: {
        dForm: [],
        inn: [],
        dType: [],
        dFarmGroup: [],
        tGroup: [],
        tradeMark: [],
        manufacturer: [[]],
    },
    listLoading: {
        dForm: false,
        inn: false,
        dType: false,
        dFarmGroup: false,
        tGroup: false,
        tradeMark: false,
        manufacturer: [false]
    },
    editData: {
        name: "",
        ref_price: 1,
        ref_price_ccy: "UZS",
        di_id: "",
        df_id: "",
        dfg_id: "",
        dtg_id: "",
        dt_id: "",
        trademark_id: "",
        is_rx: false,
        is_otc: false,
        is_active: true,
        deleted: false,
        manufacturers: [{ manufacturer_id: "", drug_mxik: "" }]
    },
    editList: {
        dForm: [],
        inn: [],
        dType: [],
        dFarmGroup: [],
        tGroup: [],
        tradeMark: [],
        manufacturer: [[]],
    },
    editListLoading: {
        dForm: false,
        inn: false,
        dType: false,
        dFarmGroup: false,
        tGroup: false,
        tradeMark: false,
        manufacturer: [false]
    }
};

export function DrugsReducer(state = initialState, action) {
    if (action.type === DRUG_ADD_DATA_ACTION) {
        return {
            ...state,
            data: { ...action.payload }
        };
    }
    if (action.type === DRUG_ADD_SELECT_ACTION) {
        return {
            ...state,
            list: { ...action.payload }
        }
    }
    if (action.type === DRUG_ADD_SELECT_LOADING_ACTION) {
        return {
            ...state,
            listLoading: { ...action.payload }
        }
    }
    if (action.type === DRUG_EDIT_DATA_ACTION) {
        return {
            ...state,
            editData: { ...action.payload }
        };
    }
    if (action.type === DRUG_EDIT_SELECT_ACTION) {
        return {
            ...state,
            editList: { ...action.payload }
        }
    }
    if (action.type === DRUG_EDIT_SELECT_LOADING_ACTION) {
        return {
            ...state,
            editListLoading: { ...action.payload }
        }
    }
    return state;
}


