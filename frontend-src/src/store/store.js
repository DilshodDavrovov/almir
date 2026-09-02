import { applyMiddleware, combineReducers, compose,createStore,} from 'redux';
import thunk from 'redux-thunk';
import { AuthReducer } from './reducers/AuthReducer';
import { LanguageReducer } from './reducers/LanguageReducer';
import { DrugsReducer } from './reducers/DrugsReducer';
import { DrcReducer } from './reducers/DrcReducer';
import { UserReducer } from './reducers/UserReducer';
import { MainReducer } from './reducers/MainReducer';
import { SaleReducer } from './reducers/SaleReducer';
const middleware = applyMiddleware(thunk);

const composeEnhancers =
    window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

const reducers = combineReducers({
    language: LanguageReducer,
    drugs: DrugsReducer,
    drc: DrcReducer,
    sale: SaleReducer,
    user: UserReducer,
    auth: AuthReducer,
    main: MainReducer
});


export const store = createStore(reducers,  composeEnhancers(middleware));
