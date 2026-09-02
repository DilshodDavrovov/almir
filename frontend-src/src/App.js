import {useEffect, useState } from 'react';

/// Components
import Index from "./jsx";
import { connect, useDispatch } from 'react-redux';
import {  Redirect, Route, Switch, withRouter } from 'react-router-dom';
// action
import Auth from './services/AuthService';
import { isAuthenticated } from './store/selectors/AuthSelectors';
/// Style
import "./vendor/bootstrap-select/dist/css/bootstrap-select.min.css";
import "./css/style.css";
import SignUp from "./jsx/pages/Auth/Registration"
import Login from "./jsx/pages/Auth/Login"

import ProductTypes from "./services/cruds/DTypesService"
import UserApi from "./services/cruds/UserService"
import SettingsApi from "./services/SettingService"
import ActivityApi from "./services/ActivityService"
import HomeApi from "./services/HomeService"
import {
    setAnalyzeActivity,
    setLastDate,
    setMacAddress,
    setProductTypes,
    setSettingsData,
    setUserInfo
} from './store/actions/MainAction';
import { logout } from './store/actions/AuthActions';
import { ToastContainer, toast } from 'react-toastify';
import {checkForMacOS, TR} from './utils/helpers';
import { checkRole } from './utils';
import Loading from './jsx/components/Loading';

function App (props) {
    const {macAddress, lang} = props
    const dispatch = useDispatch();
    const [loading, setLoading] = useState(false);
    useEffect(()=>{
        SettingsApi.getSetting().then(res => {
            dispatch(setSettingsData(res.data.data || {}));
        }).catch()
    },[])
    useEffect(() => {
        Auth.checkAutoLogin(dispatch, props.history);
    }, [dispatch, props.history]);
    useEffect(() => {
        if(isAuthenticated()){
            setLoading(true);
            UserApi.myInfo().then(res => {
                dispatch(setUserInfo(res.data.data));
                const um_expired_at = new Date(res.data.data.um_expired_at);
                const diffTime = um_expired_at - new Date();
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if(diffDays < 30 && !checkRole("1", res.data.data.user_role)) {
                    toast.error(`${TR(lang, "sidebar.expired_start")} ${diffDays > 0 ? diffDays : 0} ${TR(lang, "sidebar.expired_end")}.`, {
                        position: "top-right",
                        autoClose: 600000,
                        closeButton: true,
                        hideProgressBar: false,
                        closeOnClick: false,
                        pauseOnHover: false,
                        draggable: false,
                        progress: undefined,
                    });
                }
                HomeApi.getUpdateLog().then(res => {
                    dispatch(setLastDate(res.data.data.updated_date));
                    setLoading(false);
                }).catch(err => {
                    console.log(err);
                })
            }).catch(err => {
                // dispatch(logout(history));
                console.log(err);
            });

            ProductTypes.select(true, false).then(res => {
                const data = res.data.data || [];
                const list = [];
                data.forEach(element => {
                    list.push({label: element.full_name, value: element.id})
                })

                dispatch(setProductTypes(list));
            })

            ActivityApi.getActivity().then(res => {
                dispatch(setAnalyzeActivity(res.data.data || []));
            }).catch()

        } else {
            UserApi.getMacAddress().then(res => {
                dispatch(setMacAddress(res.data));
            }).catch(err => {});
        }

    },[isAuthenticated()])

    if (isAuthenticated()) {
		return loading ? <  Loading/> :<Index />
	} else {
		return (
			<div className="vh-100">
                <Switch>
                    <Route path='/login' component={Login} />
                    {
                        macAddress || checkForMacOS() ? <Route path='/page-register' component={SignUp} />: null
                    }
                    <Redirect to='/login'/>
                </Switch>
                <ToastContainer/>
			</div>
		);
	}
};

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        macAddress: state.main.macAddress
    };
};

export default withRouter(connect(mapStateToProps)(App)); 

