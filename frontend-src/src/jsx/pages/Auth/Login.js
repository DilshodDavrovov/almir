import React, { useState } from 'react'
import { connect, useDispatch } from 'react-redux';
import { Link, useHistory } from 'react-router-dom'
import Auth from '../../../services/AuthService'
import UserApi from '../../../services/cruds/UserService'
import logo from "../../../images/Almir-logo.png";
import { checkForMacOS, TR } from '../../../utils/helpers';
import { Alert, Button } from 'react-bootstrap'
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
    } : {};
    const { macAddress, lang, settingsData } = props;
    const s = settingsData || {};
    const history = useHistory();
    const [loading, setLoading] = useState(false);
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const dispatch = useDispatch();

    function getMac() {
        UserApi.getMacAddress().then(res => {
            dispatch(setMacAddress(res.data));
        }).catch(err => { });
    }
    function onLogin(e) {
        e.preventDefault();
        setLoading(true);
        Auth.login({ email, password, user_mac: checkForMacOS() ? "MAC_MACINTOSH" : macAddress, ...systemData })
            .then((response) => {
                setLoading(false);
                Auth.saveTokenInSessionStorage(response.data.access_token);
                history.push('/');
                showToast('success', response.data.message);
            })
            .catch((error) => {
                setLoading(false);
                showToast('error', error.response && error.response.data ? error.response.data.message : String(error));
            });
    }

    return (
        <div className="auth-page">
            <div className="auth-shell">
                <aside className="auth-side">
                    <div className="auth-logo"><img src={logo} alt="ALMIR STATISTICS" /></div>
                    <div>
                        <h1>Добро пожаловать в ALMIR</h1>
                        <p>Система предоставляет достоверную и точную статистику о лекарственных средствах, медицинских оборудованиях, биологически активных добавках, косметических и гигиенических средствах, ввозимых в Узбекистан.</p>
                        <div className="auth-badges">
                            <span><i className="fas fa-chart-pie" />Аналитика продаж</span>
                            <span><i className="fas fa-table" />Сводные отчёты</span>
                            <span><i className="fas fa-map-marked-alt" />География</span>
                        </div>
                        <div className="auth-contacts">
                            {s.contact_fax ? <span><i className="fas fa-fax" />{TR(lang, "reg.fax")}: {s.contact_fax}</span> : null}
                            {s.contact_email ? <span><i className="fas fa-envelope" />{s.contact_email}</span> : null}
                            {s.contact_phone ? <span><i className="fas fa-phone-alt" />{s.contact_phone}</span> : null}
                        </div>
                    </div>
                    <div className="auth-foot">
                        <Link to="#">Политика конфиденциальности</Link>
                        <Link to="#">Контакты</Link>
                        <span>© {new Date().getFullYear()} ALMIR STATISTICS</span>
                    </div>
                </aside>

                <main className="auth-main">
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
                    <h2>Войти</h2>
                    <p className="auth-sub">Войдите, введя информацию ниже</p>
                    {props.errorMessage && (
                        <div className="alert alert-danger">{props.errorMessage}</div>
                    )}
                    {props.successMessage && (
                        <div className="alert alert-success">{props.successMessage}</div>
                    )}
                    <form onSubmit={(e) => onLogin(e)}>
                        <div className="auth-field">
                            <label htmlFor="login-email">{TR(lang, "auth.login")}</label>
                            <input id="login-email" type="email" className="form-control"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder='example@example.com'
                                autoComplete="username"
                            />
                        </div>
                        <div className="auth-field">
                            <label htmlFor="login-password">{TR(lang, "auth.password")}</label>
                            <input
                                id="login-password"
                                type="password"
                                className="form-control"
                                value={password}
                                placeholder='••••••••••'
                                autoComplete="current-password"
                                onChange={(e) => setPassword(e.target.value)}
                            />
                        </div>
                        <div className="auth-row">
                            <div className="form-check">
                                <input type="checkbox" className="form-check-input" id="basic_checkbox_1" />
                                <label className="form-check-label" htmlFor="basic_checkbox_1">Запомнить мои предпочтения</label>
                            </div>
                        </div>
                        <button disabled={loading} type="submit" className="btn btn-primary auth-btn">
                            {loading ? 'Загрузка...' : 'Войти'}
                        </button>
                    </form>
                    <div className="auth-alt">
                        У вас нет аккаунта? <Link to="./page-register">Регистрация</Link>
                    </div>
                </main>
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
