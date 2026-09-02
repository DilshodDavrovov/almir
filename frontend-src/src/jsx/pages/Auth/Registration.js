import React,{useState} from "react";
import { Link } from "react-router-dom";
import { connect } from 'react-redux';
import logo from "../../../images/logo-full.png";
import {checkForMacOS, genPass, TR} from "../../../utils/helpers";
import Auth from '../../../services/AuthService'
import { showToast } from "../../../utils";
import { useHistory } from "react-router-dom/cjs/react-router-dom.min";

function Register(props) {
    const systemData = checkForMacOS() ? {
        browserName: window.navigator.userAgent,
        platform: window.navigator.platform,
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        browserLanguage: window.navigator.language,
        areCookiesEnabled: window.navigator.cookieEnabled,
        isOnline: window.navigator.onLine
    }: {};
    const history = useHistory();
    const {lang, macAddress} = props;
    const [data,setData] = useState({
        first_name:"",
        last_name:"",
        middle_name:"",
        company_name:"",
        phone_number:"",
        email:"",
        password:"",
        confirm_password:"",
        user_address:"",
        user_mac: checkForMacOS() ? "MAC_MACINTOSH" :  macAddress,
        role:0
    });
    const [eye, setEye] = useState(false);

    function onSignUp(e) {
        e.preventDefault();
        Auth.signUp({...data, ...systemData})
        .then((response) => {
            history.push('/login');
            showToast('success', response.data.message);
        }).catch((error) => {
            showToast('error', error.response.data.message);
        });
    }
  return (
    <div className="authincation h-100 p-meddle">
      <div className="container h-100">
        <div className="row justify-content-center h-100 align-items-center">
          <div className="col-md-8">
            <div className="authincation-content">
              <div className="row no-gutters">
                <div className="col-xl-12">
                  <div className="auth-form">
                    <div className="text-center mb-3">
                      <Link to="/login">
                        <img width={"300px"} height={"70px"} src={logo} alt="" />
                      </Link>
                    </div>
                    <h4 className="text-center mb-4 ">{TR(lang, "auth.signUp")}</h4>
					{props.errorMessage && (
						<div className=''>
							{props.errorMessage}
						</div>
					)}
					{props.successMessage && (
						<div className=''>
							{props.successMessage}
						</div>
					)}
                    <form onSubmit={onSignUp}>
                    <div className="form-group mb-3">
                        <label htmlFor='fname' className="mb-1">{TR(lang, "reg.name")}</label>
                        <input id='fname' className="form-control" onChange={e=>setData({...data,first_name: e.target.value})} name={"fname"} type = 'text' placeholder = {TR(lang, "reg.placeName")} required/>
                    </div>
                    <div className="form-group mb-3">
                        <label htmlFor='last_name' className="mb-1">{TR(lang, "reg.secName")}</label>
                        <input id='last_name' className="form-control" onChange={e=>setData({...data,last_name: e.target.value})} name={"last_name"} type = 'text' placeholder = {TR(lang, "reg.placeSecName")} required/>
                    </div>
                    <div className="form-group mb-3">
                        <label htmlFor='middle_name' className="mb-1">{TR(lang, "reg.lastName")}</label>
                        <input id='middle_name' className="form-control"onChange={e=>setData({...data,middle_name: e.target.value})} name="middle_name" type = 'text' placeholder = {TR(lang, "reg.placeLastName")} required/>
                    </div>
                    <div className="form-group mb-3">
                        <label htmlFor='company_name' className="mb-1">{TR(lang, "reg.compName")}</label>
                        <input id='company_name' className="form-control"onChange={e=>setData({...data,company_name: e.target.value})} name={"company_name"} type = 'text' placeholder = 'Farma Lux International' required/>
                    </div>
                    <div className="form-group mb-3">
                        <label htmlFor='phone_number' className="mb-1">{TR(lang, "reg.phone")}</label>
                        <input id='phone_number' className="form-control" onChange={e=>setData({...data,phone_number: e.target.value})} name={"phone_number"} type = 'text' placeholder = '+99890 777-77-77'  required/>
                    </div>
                    <div className="form-group mb-3">
                        <label htmlFor='email' className="mb-1">{TR(lang, "auth.email")}</label>
                        <input id='email' className="form-control" onChange={e=>setData({...data,email: e.target.value})} name={"email"} type = 'email' placeholder = 'example@example.com' required/>
                    </div>
                    <div className = "form-group mb-3">
                        <div className="d-flex justify-content-between">
                            <label htmlFor='password' className="mb-1">{TR(lang, "auth.password")}</label>
                            <label
                                className="text-primary cursor-pointer"
                                onClick={() => setData({...data,password: genPass(10)})}>
                                {TR(lang, "reg.genPass")}
                            </label>
                        </div>
                        <div className="d-flex justify-content-between">
                            <input 
                                id='password' 
                                className="form-control"
                                value={data.password}
                                onChange={e=>setData({...data,password: e.target.value})}
                                name={"password"} type = {`${(eye)? 'text' : 'password'}`}
                                placeholder = '**********' 
                                required/>
                            <i style={{marginLeft: "-30px", marginRight: "10px"}} className={`d-flex align-items-center far ${(eye)?  'fa-eye' : 'fa-eye-slash'}`} onClick={() => setEye(!eye)}></i>
                        </div>
                    </div>
                    <div className = "form-group mb-3">
                        <label htmlFor='confirm-password' className="mb-1">{TR(lang, "reg.confirmPass")}</label>
                        <div className="d-flex justify-content-between">
                            <input 
                                id='confirm-password' 
                                className="form-control"
                                value={data.confirm_password}
                                onChange={e=>setData({...data, confirm_password: e.target.value})}
                                name={"password"} type = {`${(eye)? 'text' : 'password'}`}
                                placeholder = '**********' 
                                required/>
                            <i style={{marginLeft: "-30px", marginRight: "10px"}} className={`d-flex align-items-center far ${(eye)?  'fa-eye' : 'fa-eye-slash'}`} onClick={() => setEye(!eye)}></i>
                        </div>
                    </div>
                    <div className="text-center mt-4">
                    <button
                        type="submit"
                        className="btn btn-primary btn-block"
                    > Регистрация
                    </button>
                    </div>
                    </form>
                    <div className="new-account mt-3">
                      <p className="">
                        Уже есть аккаунт?{" "}
                        <Link className="text-primary" to="/login">
                            Войти
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
        macAddress: state.main.macAddress,
        lang: state.language.lang,
        errorMessage: state.auth.errorMessage,
        successMessage: state.auth.successMessage
    };
};

export default connect(mapStateToProps)(Register);

