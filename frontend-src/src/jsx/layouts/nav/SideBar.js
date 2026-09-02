/// Menu
import Metismenu from "metismenujs";
import React, { Component, useContext, useEffect, useState } from "react";

/// Scroll
import PerfectScrollbar from "react-perfect-scrollbar";
/// Link
import { Link, withRouter, useHistory } from "react-router-dom";
import { Dropdown } from "react-bootstrap";
import useScrollPosition from "use-scroll-position";
import { ThemeContext } from "../../../context/ThemeContext";
/// Image
import profile from "../../../images/user.png";
import { connect, useDispatch } from 'react-redux';
import { TR } from "../../../utils/helpers";
import ChangePasswordModal from "../../components/Modals/ChangePasswordModal";
import { baseURL } from "../../../services/AxiosInstance";
import { logout } from "../../../store/actions/AuthActions";
import { checkRole } from "../../../utils";





class MM extends Component {
    componentDidMount() {
        this.$el = this.el;
        this.mm = new Metismenu(this.$el);
    }
    componentWillUnmount() {
    }
    render() {
        return (
            <div className="mm-wrapper">
                <ul className="metismenu" ref={(el) => (this.el = el)}>
                    {this.props.children}
                </ul>
            </div>
        );
    }
}

const SideBar = (props) => {
    const { lang, settingsData, userInfo, role } = props;
    const [show, setShow] = useState(false);
    const dispatch = useDispatch();
    const history = useHistory();
    const {
        iconHover,
        sidebarposition,
        headerposition,
        sidebarLayout,
    } = useContext(ThemeContext);
    const file_list = {
        referent_cost_file: settingsData.referent_cost_file,
        reg_cost_glc_file: settingsData.reg_cost_glc_file,
        customer_cost_file: settingsData.customer_cost_file
    }
    useEffect(() => {
        var btn = document.querySelector(".nav-control");
        var aaa = document.querySelector("#main-wrapper");
        function toggleFunc() {
            return aaa.classList.toggle("menu-toggle");
        }
        btn.addEventListener("click", toggleFunc);
    }, []);

    let scrollPosition = useScrollPosition();
    let path = window.location.pathname;

    return (
        <div
            className={`dlabnav ${iconHover} ${(sidebarposition.value === "fixed" &&
                sidebarLayout.value === "horizontal" &&
                headerposition.value === "static")
                ? scrollPosition > 120
                    ? "fixed"
                    : ""
                : ""
                }`}
        >
            <PerfectScrollbar className="dlabnav-scroll">
                <MM className="metismenu" id="menu">
                    <Dropdown as="li" className="nav-item dropdown header-profile">
                        <Dropdown.Toggle
                            variant=""
                            as="a"
                            className="nav-link i-false c-pointer"
                            // href="#"
                            role="button"
                            data-toggle="dropdown"
                        >
                            <img src={profile} width={20} alt="" />
                        </Dropdown.Toggle>

                        <Dropdown.Menu align="right" className="mt-2">
                            <Dropdown.Header className="text-white text-center h5" >{`${userInfo.first_name} ${userInfo.last_name}`}</Dropdown.Header>
                            <Dropdown.Item onClick={() => setShow(true)}>
                                <i className="text-white fas fa-key"></i>
                                <span>{TR(lang, 'auth.changePass')}</span>
                            </Dropdown.Item>
                            {
                                 checkRole("1", role) ?
                                 <Dropdown.Item onClick={() => history.push('/profile/settings')}>
                                    <i className="text-white fas fa-gear"></i>
                                    <span>{TR(lang, 'content.settings')}</span>
                                </Dropdown.Item> 
                                :null
                            }
                            
                            <Dropdown.Item  onClick={() => dispatch(logout(props.history))}>
                                <i className="text-white fa fa-sign-out"></i>
                                <span>{TR(lang, 'navBar.logOut')}</span>
                            </Dropdown.Item>
                            
                        </Dropdown.Menu>
                    </Dropdown>
                    <li className={`${path === '/' ? "mm-active" : ""}`}>
                        <Link className="d-flex align-items-center" to="/" >
                            <i className="fas fa-home" aria-hidden='true'></i>
                            <span className="nav-text">{TR(lang, "sidebar.Home")}</span>
                        </Link>
                    </li>
                    <li className={`${path.includes('/analytics') ? "mm-active" : ""}`}>
                        <Link className="ai-icon d-flex align-items-center" to="/analytics" >
                            <i className="fas fa-chart-pie" aria-hidden='true'></i>
                            <span className="nav-text">{TR(lang, "sidebar.Analytics")}</span>
                        </Link>
                    </li>
                    <li className={`${path.includes('/pivot') ? "mm-active" : ""}`}>
                        <Link className="ai-icon d-flex align-items-center" to="/pivot" >
                            <i className="fas fa-table-cells" aria-hidden='true'></i>
                            <span className="nav-text">{TR(lang, "sidebar.Pivot")}</span>
                        </Link>
                    </li>

                    {
                        checkRole("2", role) ? 
                        <li className={`${path.includes('/admin') ? "mm-active" : ""}`}>
                            <Link className="has-arrow ai-icon d-flex align-items-center" to="#" >
                                <i className="fas fa-pills"></i>
                                <span className="nav-text">{TR(lang, "sidebar.Products")}</span>
                            </Link>
                            <ul>
                                <li><Link className={`${path === "/admin/drc" ? "mm-active" : ""}`} to="/admin/drc">{TR(lang, "products.drc")}</Link></li>
                                <li><Link className={`${path === "/admin/sale" ? "mm-active" : ""}`} to="/admin/sale">{TR(lang, "products.sale")}</Link></li>
                                <li><Link className={`${path === "/admin/drugs" ? "mm-active" : ""}`} to="/admin/drugs">{TR(lang, "products.med")}</Link></li>
                                <li><Link className={`${path === "/admin/d-forms" ? "mm-active" : ""}`} to="/admin/d-forms">{TR(lang, "products.df")}</Link></li>
                                <li><Link className={`${path === "/admin/countries" ? "mm-active" : ""}`} to="/admin/countries">{TR(lang, "products.mfc")}</Link></li>
                                <li><Link className={`${path === "/admin/regions" ? "mm-active" : ""}`} to="/admin/regions">{TR(lang, "products.region")}</Link></li>
                                <li><Link className={`${path === "/admin/districts" ? "mm-active" : ""}`} to="/admin/districts">{TR(lang, "products.district")}</Link></li>
                                <li><Link className={`${path === "/admin/d-farm-groups" ? "mm-active" : ""}`} to="/admin/d-farm-groups">{TR(lang, "products.dfg")}</Link></li>
                                <li><Link className={`${path === "/admin/distributors" ? "mm-active" : ""}`} to="/admin/distributors">{TR(lang, "products.dist")}</Link></li>
                                <li><Link className={`${path === "/admin/counterparty" ? "mm-active" : ""}`} to="/admin/counterparty">{TR(lang, "products.counterparty")}</Link></li>
                                <li><Link className={`${path === "/admin/d-types" ? "mm-active" : ""}`} to="/admin/d-types">{TR(lang, "products.dt")}</Link></li>
                                <li><Link className={`${path === "/admin/inn" ? "mm-active" : ""}`} to="/admin/inn">{TR(lang, "products.mnn")}</Link></li>
                                <li><Link className={`${path === "/admin/manufacturers" ? "mm-active" : ""}`} to="/admin/manufacturers">{TR(lang, "products.mf")}</Link></li>
                                <li><Link className={`${path === "/admin/suppliers" ? "mm-active" : ""}`} to="/admin/suppliers">{TR(lang, "products.senders")}</Link></li>
                                <li><Link className={`${path === "/admin/t-groups" ? "mm-active" : ""}`} to="/admin/t-groups">{TR(lang, "products.tpg")}</Link></li>
                                <li><Link className={`${path === "/admin/trade-marks" ? "mm-active" : ""}`} to="/admin/trade-marks">{TR(lang, "products.td")}</Link></li>
                                <li>
                                    <a target="_blank" rel="noreferrer"
                                        href={`${baseURL}/public${file_list.referent_cost_file}`}>
                                        {TR(lang, "content.uploadReferentPrices")}
                                    </a>
                                </li>
                                <li>
                                    <a target="_blank" rel="noreferrer"
                                        href={`${baseURL}/public${file_list.reg_cost_glc_file}`}>
                                        {TR(lang, "content.uploadRegisteredGls")}
                                    </a>
                                </li>
                                <li>
                                    <a target="_blank" rel="noreferrer"
                                        href={`${baseURL}/public${file_list.customer_cost_file}`}>
                                        {TR(lang, "content.uploadValueAddedTax")}
                                    </a>
                                </li>
                            </ul>
                        </li> : null
                    }
                    <li className={`${path.includes('/analyze') ? "mm-active" : ""}`}>
                        <Link className="has-arrow ai-icon d-flex align-items-center" to="#">
                            <i className="fas fa-diagnoses" />
                            <span className="nav-text">{TR(lang, "sidebar.Analyzes")}</span>
                        </Link>
                        <ul>
                            <li><Link className={`${path === "/analyze/drugs" ? "mm-active" : ""}`} to="/analyze/drugs">{TR(lang, "analyzes.names")}</Link></li>
                            <li><Link className={`${path === "/analyze/d-form" ? "mm-active" : ""}`} to="/analyze/d-form">{TR(lang, "analyzes.df")}</Link></li>
                            <li><Link className={`${path === "/analyze/companies" ? "mm-active" : ""}`} to="/analyze/companies">{TR(lang, "analyzes.companies")}</Link></li>
                            <li><Link className={`${path === "/analyze/distributors" ? "mm-active" : ""}`} to="/analyze/distributors">{TR(lang, "analyzes.dist")}</Link></li>
                            <li><Link className={`${path === "/analyze/manufacturers" ? "mm-active" : ""}`} to="/analyze/manufacturers">{TR(lang, "analyzes.mf")}</Link></li>
                            <li><Link className={`${path === "/analyze/trademark" ? "mm-active" : ""}`} to="/analyze/trademark">{TR(lang, "analyzes.td")}</Link></li>
                            <li><Link className={`${path === "/analyze/inn" ? "mm-active" : ""}`} to="/analyze/inn">{TR(lang, "analyzes.mnn")}</Link></li>
                            <li><Link className={`${path === "/analyze/d-farm-groups" ? "mm-active" : ""}`} to="/analyze/d-farm-groups">{TR(lang, "analyzes.dfg")}</Link></li>
                            <li><Link className={`${path === "/analyze/t-groups" ? "mm-active" : ""}`} to="/analyze/t-groups">{TR(lang, "analyzes.tpg")}</Link></li>
                        </ul>
                    </li>
                    {/*<li className={`${path.includes('/search') ? "mm-active" : ""}`}>*/}
                    {/*    <Link className="has-arrow ai-icon d-flex align-items-center" to="#">*/}
                    {/*        <i className="fas fa-search"></i>*/}
                    {/*        <span className="nav-text">{TR(lang, 'sidebar.Search')}</span>*/}
                    {/*    </Link>*/}
                    {/*    <ul>*/}
                    {/*        <li><Link className={`${path === "/search-new" ? "mm-active" : ""}`} to="/search-new">{TR(lang, "sidebar.SearchNew")}</Link></li>*/}
                    {/*        <li><Link className={`${path === "/search" ? "mm-active" : ""}`} to="/search">{TR(lang, "sidebar.SearchOld")}</Link></li>*/}
                    {/*    </ul>*/}
                    {/*</li>*/}
                    <li className={`${path.includes('/search-new') ? "mm-active" : ""}`}>
                        <Link className="ai-icon" to="/search-new" >
                            <i className="fas fa-search"></i>
                            <span className="nav-text">{TR(lang, 'sidebar.Search')}</span>
                        </Link>
                    </li>
                    <li className={`${path.includes('/news') ? "mm-active" : ""}`}>
                        <Link className="ai-icon" to="/news" >
                            <i className="fas fa-newspaper"></i>
                            <span className="nav-text">{TR(lang, 'sidebar.News')}</span>
                        </Link>
                    </li>
                    {
                        checkRole("1", role) ?
                        <li className={`${path.includes('/user') ? "mm-active" : ""}`}>
                            <Link className="ai-icon" to="/user" >
                                <i className="fas fa-users"></i>
                                <span className="nav-text">{TR(lang, 'sidebar.Users')}</span>
                            </Link>
                        </li>
                        :null
                    }
                </MM>
            </PerfectScrollbar>
            <ChangePasswordModal
                show={show}
                setShow={setShow}
            />
        </div>
    );
};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        data: state.user.data,
        settingsData: state.main.settingsData,
        userInfo: state.main.userInfo,
        role: state.main.userInfo ? state.main.userInfo.user_role : null 

    };
};

export default withRouter(connect(mapStateToProps)(SideBar));