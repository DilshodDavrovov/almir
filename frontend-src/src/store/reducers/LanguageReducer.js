import {
    CHANGE_LANGUAGE_ACTION
} from '../actions/LanguageActions';

const initialState = {
    lang: 'рус'
};

export function LanguageReducer(state = initialState, action) {
    if (action.type === CHANGE_LANGUAGE_ACTION) {
        return {
            ...state,
            lang: action.payload
        };
    }
    return state;
}

    
