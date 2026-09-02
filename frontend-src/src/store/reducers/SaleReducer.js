import {
    SALE_TABLE_PRICE,
    SALE_TABLE_C_PRICE,
    SALE_TABLE_SUM_PRICE,
    SALE_ADD_DATA_ACTION,
    SALE_ADD_SELECT_ACTION,
    SALE_ADD_SELECT_LOADING_ACTION,
    SALE_EDIT_DATA_ACTION,
    SALE_EDIT_SELECT_ACTION,
    SALE_EDIT_SELECT_LOADING_ACTION,
    SALE_CONVERT_PRICE_LOADING_ACTION,
    SALE_CONVERT_C_PRICE_LOADING_ACTION
} from '../actions/SaleAction';

const initialState = {
    data: {
        data_type: 2,
        "drug_id": "",
        "serial_number": null,
        "shelf_life": null,
        "mode_70_date": null,
        "m70d_id": null,
        "mode_40_date": new Date(),
        "m40d_id": "",
        "sc_id": null,
        "country_id": 19,
        "region_id": "",
        "district_id": "",
        "counterparty_id": "",
        "period_code": "",
        "c_price_ccy": "UZS",
        "c_price_ccy_rate": "",
        "c_price_uzs": "",
        "c_price_usd": "",
        "c_price_eur": "",
        "c_price_rub": "",
        "price_ccy": "UZS",
        "price_ccy_rate": "",
        "price_usd": "",
        "price_uzs": "",
        "price_eur": "",
        "price_rub": "",
        "quantity": "",
        "sum_price_usd": "",
        "sum_price_uzs": "",
        "sum_price_eur": "",
        "sum_price_rub": "",
        "is_local": false,
    },
    list: {
        drug: [],
        distributor_40: [],
        // country: [],
        // region: [],
        // district: [],
        counterparty: []
    },
    listLoading: {
        drug: false,
        distributor_40: false,
        // country: false,
        // region: false,
        // district: false,
        counterparty: false
    },
    editData: {
        data_type: 2,
        "drug_id": "",
        "serial_number": null,
        "shelf_life": null,
        "mode_70_date": null,
        "m70d_id": null,
        "mode_40_date": new Date(),
        "m40d_id": "",
        "sc_id": null,
        "country_id": 19,
        "region_id": "",
        "district_id": "",
        "counterparty_id": "",
        "period_code": "",
        "c_price_ccy": "UZS",
        "c_price_ccy_rate": "",
        "c_price_uzs": "",
        "c_price_usd": "",
        "c_price_eur": "",
        "c_price_rub": "",
        "price_ccy": "UZS",
        "price_ccy_rate": "",
        "price_usd": "",
        "price_uzs": "",
        "price_eur": "",
        "price_rub": "",
        "quantity": "",
        "sum_price_usd": "",
        "sum_price_uzs": "",
        "sum_price_eur": "",
        "sum_price_rub": "",
        "is_local": false,
    },
    editList: {
        drug: [],
        distributor_40: [],
        // country: [],
        // region: [],
        // district: [],
        counterparty: []
    },
    editListLoading: {
        drug: false,
        distributor_40: false,
        // country: false,
        // region: false,
        // district: false,
        counterparty: false
    },
    price_loading: false,
    c_price_loading: false,
    price: 'usd',
    c_price: 'usd',
    sum_price: 'usd'
};

export function SaleReducer(state = initialState, action) {
    if (action.type === SALE_TABLE_PRICE) {
        return {
            ...state,
            price: action.payload
        };
    }
    if (action.type === SALE_TABLE_C_PRICE) {
        return {
            ...state,
            c_price: action.payload
        };
    }
    if (action.type === SALE_TABLE_SUM_PRICE) {
        return {
            ...state,
            sum_price: action.payload
        };
    }
    if (action.type === SALE_ADD_DATA_ACTION) {
        return {
            ...state,
            data: { ...action.payload }
        };
    }
    if (action.type === SALE_ADD_SELECT_ACTION) {
        return {
            ...state,
            list: { ...action.payload }
        }
    }
    if (action.type === SALE_ADD_SELECT_LOADING_ACTION) {
        return {
            ...state,
            listLoading: { ...action.payload }
        }
    }
    if (action.type === SALE_EDIT_DATA_ACTION) {
        return {
            ...state,
            editData: { ...action.payload }
        };
    }
    if (action.type === SALE_EDIT_SELECT_ACTION) {
        return {
            ...state,
            editList: { ...action.payload }
        }
    }
    if (action.type === SALE_EDIT_SELECT_LOADING_ACTION) {
        return {
            ...state,
            editListLoading: { ...action.payload }
        }
    }
    if (action.type === SALE_CONVERT_PRICE_LOADING_ACTION) {
        return {
            ...state,
            price_loading: action.payload
        }
    }
    if (action.type === SALE_CONVERT_C_PRICE_LOADING_ACTION) {
        return {
            ...state,
            c_price_loading: action.payload
        }
    }
    return state;
}


