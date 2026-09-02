import {
    DRC_TABLE_PRICE, 
    DRC_TABLE_C_PRICE, 
    DRC_TABLE_SUM_PRICE, 
    DRC_ADD_DATA_ACTION, 
    DRC_ADD_SELECT_ACTION, 
    DRC_ADD_SELECT_LOADING_ACTION, 
    DRC_EDIT_DATA_ACTION, 
    DRC_EDIT_SELECT_ACTION, 
    DRC_EDIT_SELECT_LOADING_ACTION,
    DRC_CONVERT_PRICE_LOADING_ACTION,
    DRC_CONVERT_C_PRICE_LOADING_ACTION
} from '../actions/DrcAction';

const initialState = {
    data: {
        "drug_id": "",
        "serial_number": "",
        "shelf_life": new Date(),
        "mode_70_date": new Date(),
        "m70d_id": "",
        "mode_40_date": new Date(),
        "m40d_id": "",
        "sc_id": "",
        "counterparty_id": "",
        "period_code": "",
        "c_price_ccy": "USD",
        "c_price_ccy_rate": "",
        "c_price_uzs": "",
        "c_price_usd": "",
        "c_price_eur": "",
        "c_price_rub": "",
        "price_ccy": "USD",
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
        "is_local": false
    },
    list: {
        drug: [],
        distributor_40: [],
        distributor_70: [],
        company: [],
        counterparty: []
    },
    listLoading: {
        drug: false,
        distributor_40: false,
        distributor_70: false,
        company: false,
        counterparty: false
    },
    editData: {
        "drug_id": "",
        "serial_number": "",
        "shelf_life": new Date(),
        "mode_70_date": new Date(),
        "m70d_id": "",
        "mode_40_date": new Date(),
        "m40d_id": "",
        "sc_id": "",
        "counterparty_id": "",
        "period_code": "",
        "c_price_ccy": "USD",
        "c_price_ccy_rate": "",
        "c_price_uzs": "",
        "c_price_usd": "",
        "c_price_eur": "",
        "c_price_rub": "",
        "price_ccy": "USD",
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
        "is_local": false
    },
    editList: {
        drug: [],
        distributor_40: [],
        distributor_70: [],
        company: [],
        counterparty: []
    },
    editListLoading: {
        drug: false,
        distributor_40: false,
        distributor_70: false,
        company: false,
        counterparty: false
    },
    price_loading: false,
    c_price_loading: false,
    price: 'usd',
    c_price: 'usd',
    sum_price: 'usd'
};

export function DrcReducer(state = initialState, action) {
    if (action.type === DRC_TABLE_PRICE) {
        return {
            ...state,
            price: action.payload
        };
    }
    if (action.type === DRC_TABLE_C_PRICE) {
        return {
            ...state,
            c_price: action.payload
        };
    }
    if (action.type === DRC_TABLE_SUM_PRICE) {
        return {
            ...state,
            sum_price: action.payload
        };
    }
    if (action.type === DRC_ADD_DATA_ACTION) {
        return {
            ...state,
            data: {...action.payload}
        };
    }
    if(action.type === DRC_ADD_SELECT_ACTION){
        return {
            ...state,
            list: {...action.payload}
        }
    }
    if(action.type === DRC_ADD_SELECT_LOADING_ACTION){
        return {
            ...state,
            listLoading: {...action.payload}
        }
    }
    if (action.type === DRC_EDIT_DATA_ACTION) {
        return {
            ...state,
            editData: {...action.payload}
        };
    }
    if(action.type === DRC_EDIT_SELECT_ACTION){
        return {
            ...state,
            editList: {...action.payload}
        }
    }
    if(action.type === DRC_EDIT_SELECT_LOADING_ACTION){
        return {
            ...state,
            editListLoading: {...action.payload}
        }
    }
    if(action.type === DRC_CONVERT_PRICE_LOADING_ACTION){
        return {
            ...state,
            price_loading: action.payload 
        }
    }
    if(action.type === DRC_CONVERT_C_PRICE_LOADING_ACTION){
        return {
            ...state,
            c_price_loading: action.payload 
        }
    }
    return state;
}

    
