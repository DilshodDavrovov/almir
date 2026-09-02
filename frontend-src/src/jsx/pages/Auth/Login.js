import React, { useState } from 'react'
import { connect, useDispatch } from 'react-redux';
import { Link, useHistory } from 'react-router-dom'
import Auth from '../../../services/AuthService'
// image
import UserApi from '../../../services/cruds/UserService'
import loginbg from "../../../images/bg-login.jpg";
import {checkForMacOS, TR} from '../../../utils/helpers';
import {Alert, Button} from 'react-bootstrap'
import { setMacAddress } from '../../../store/actions/MainAction';
import { baseURL } from '../../../services/AxiosInstance';
import { showToast } from '../../../utils';
function Login(props) {
    const systemData = checkForMacOS() ? {
        browserName: window.navigator.userAgent,
        platform: window.navigator.platform,
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        browserLanguage: window.navigator.language,
        areCookiesEnabled: window.navigator.cookieEnabled,
        isOnline: window.navigator.onLine
    }: {};
    const {macAddress, lang, settingsData} = props;
    const history = useHistory();
    const [loading, setLoading] = useState(false);
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const dispatch = useDispatch();

    function getMac(){
        UserApi.getMacAddress().then(res => {
            dispatch(setMacAddress(res.data));
        }).catch(err => {});
    }
    function onLogin(e) {
        e.preventDefault();
        setLoading(true);
        Auth.login({email, password, user_mac: checkForMacOS() ? "MAC_MACINTOSH" :  macAddress, ...systemData})
            .then((response) => {
                setLoading(false);
                Auth.saveTokenInSessionStorage(response.data.access_token);
                history.push('/');
                showToast('success', response.data.message);
            })
            .catch((error) => {
                setLoading(false);
                showToast('error', error.response.data.message);
            });
    }

    return (
        <div className="login-main-page" style={{ backgroundImage: "url(" + loginbg + ")", height: "100%" }}>
            <div className="login-wrapper">
                <div className="login-aside-left" >
                    <div className="login-description">
                        <h2 className="main-title mb-2">Добро пожаловать в ALMIR</h2>
                        <p className="">Система предоставляет достоверную и точную статистику о лекарственных средствах, медицинских оборудованиях, биологически активных добавках, косметических и гигиенических средствах ввозимых в Узбекистан.</p>
                        <ul className="social-icons mt-4">
                            <li><a target="_blank" href="https://facebook.com"><i className="fa fa-facebook"></i></a></li>
                            <li><a target="_blank" href="https://twitter.com"><i className="fa fa-twitter"></i></a></li>
                            <li><a target="_blank" href="https://t.me/+998999999999"><i className="fa fa-telegram"></i></a></li>
                            <li><a target="_blank" href="https://linkedin.com"><i className="fa fa-linkedin"></i></a></li>
                        </ul>
                        <div className="mt-3 bottom-privacy">
                            <p className="m-0 ps-2">{TR(lang, "reg.fax")}: {settingsData.contact_fax}</p>
                            <p className="m-0 ps-2">{TR(lang, "auth.email")}: {settingsData.contact_email}</p>
                            <p className="m-0 ps-2">{TR(lang, "reg.phone")}: {settingsData.contact_phone}</p>
                        </div>
                        <div className="mt-3 bottom-privacy">
                            <Link to={"#"} className="mx-2">Политика конфиденциальности</Link>
                            <Link to={"#"} className="mx-2">Контакты</Link>
                            <Link to={"#"} className="mx-2">© {new Date().getFullYear()} ALMIR STATISTICS</Link>
                        </div>
                    </div>
                </div>
                <div className="login-aside-right">
                    <div className="row m-0 justify-content-center h-100 align-items-center p-3">
                        {
                            !macAddress ? 
                            <Alert variant="danger">{TR(lang, "content.signUpTitle")}
                                <br />
                                <a href={`${baseURL}/public/docs/installer.zip`}>{TR(lang, "content.downMod")}</a>
                                <hr />
                                {TR(lang, "content.tryAg")} :{"   "}
                                <Button variant="info" onClick={getMac} size="sm"><i className="fa fa-refresh"></i></Button>
                            </Alert> : null
                        }
                        <div className="authincation-content">
                            <div className="row no-gutters">
                                <div className="col-xl-12">
                                    <div className="auth-form-1">
                                        <div className="mb-4">
                                            <h3 className="dz-title mb-1">Войти</h3>
                                            <p className="">Войдите, введя информацию ниже</p>
                                        </div>
                                        {props.errorMessage && (
                                            <div className='bg-red-300 text-red-900 border border-red-900 p-1 my-2'>
                                                {props.errorMessage}
                                            </div>
                                        )}
                                        {props.successMessage && (
                                            <div className='bg-green-300 text-green-900 border border-green-900 p-1 my-2'>
                                                {props.successMessage}
                                            </div>
                                        )}
                                        <form onSubmit={(e) => onLogin(e)}>
                                            <div className="form-group">
                                                <label className="mb-2 ">
                                                    <strong>Логин</strong>
                                                </label>
                                                <input type="email" className="form-control"
                                                    value={email}
                                                    onChange={(e) => setEmail(e.target.value)}
                                                    placeholder = 'example@example.com'
                                                />
                                            </div>
                                            <div className="form-group">
                                                <label className="mb-2 "><strong>Пароль</strong></label>
                                                <input
                                                    type="password"
                                                    className="form-control"
                                                    value={password}
                                                    placeholder = '**********'
                                                    onChange={(e) =>
                                                        setPassword(e.target.value)
                                                    }
                                                />
                                            </div>
                                            <div className="form-row d-flex justify-content-between mt-4 mb-2">
                                                <div className="form-group">
                                                    <div className="custom-control custom-checkbox ml-1 ">
                                                        <input
                                                            type="checkbox"
                                                            className="custom-control-input"
                                                            id="basic_checkbox_1"
                                                        />
                                                        <label
                                                            className="custom-control-label"
                                                            htmlFor="basic_checkbox_1"
                                                        >
                                                            Запомнить мои предпочтения
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="text-center">
                                                <button
                                                    disabled={loading}
                                                    type="submit"
                                                    className="btn btn-primary btn-block"
                                                >
                                                    {loading ? 'Загрузка...' : 'Войти'}
                                                </button>
                                            </div>
                                        </form>
                                        <div className="new-account mt-2">
                                            <p className="">
                                                У вас нет аккаунта? {" "}
                                                <Link className="text-primary" to="./page-register">
                                                    Регистрация 
                                                </Link>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        settingsData: state.main.settingsData,
        macAddress: state.main.macAddress,
        errorMessage: state.auth.errorMessage,
        successMessage: state.auth.successMessage
    };
};
export default connect(mapStateToProps)(Login);
