import React, { useEffect, useState } from "react";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
// import {FloatingLabel, Form} from 'react-bootstrap';
// import Form from 'react-bootstrap/Form';

import { Link, withRouter } from "react-router-dom";
/// Scroll
import PerfectScrollbar from "react-perfect-scrollbar";
import { ReactComponent as EngFlag } from "../../../icons/flags/eng.svg";
import { ReactComponent as RuFlag } from "../../../icons/flags/rus.svg";
import { ReactComponent as UzFlag } from "../../../icons/flags/uzb.svg";
/// Image
import { Dropdown, DropdownButton } from "react-bootstrap";
// import Dropdown from "react-bootstrap/Dropdown";
// import DropdownButton from "react-bootstrap/DropdownButton";
import { useDispatch, connect } from "react-redux";
import { handleLanguageAction } from "../../../store/actions/LanguageActions";

import { TR } from "../../../utils/helpers";
import SupportModal from "../../components/Modals/SupportModal";

const Header = ({lang }) => {
  const dispatch = useDispatch();
  // Header Scroll animation
  useEffect(() => {
    window.addEventListener("scroll", isSticky);
    return () => {
      window.removeEventListener("scroll", isSticky);
    };
  });

  const isSticky = (e) => {
    const header = document.querySelector(".header");
    const scrollTop = window.scrollY;
    scrollTop >= 60
      ? header.classList.add("is-sticky")
      : header.classList.remove("is-sticky");
  };

  const handleLangChange = (lang) => {
    dispatch(handleLanguageAction(lang));
  };

  const [show, setShow] = useState(false);

  return (
    <>
      <div className="header">
        <div className="header-content">
          <nav className="navbar navbar-expand">
            <div className="collapse navbar-collapse justify-content-between">
              <div className="header-left"></div>
              <ul className="navbar-nav header-right main-notification d-flex align-items-center">
              <div className="dropdown export-drop help-drop">
                  <button className="btn btn-primary dropdown-toggle media-btn d-flex align-items-center juctify-content-center mx-2 rounded-2 drop-export" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                  <i className="fas fa-cog mr-1 me-2" aria-hidden="true"></i>
                    <span>{TR(lang, "sidebar.Help")}</span>
                  </button>
                  <ul className="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li className="d-flex align-items-start juctify-content-start">
                    <button
                      type="button"
                      className="btn btn-outline-primary hover-btn m-0 p-0 border-0"
                    >
                      <i
                        className="fas fa-chevron-right mr-1 me-2"
                        aria-hidden="true"
                      ></i>
                      {TR(lang, "help.forAdmin")}
                    </button>
                    </li>
                    <li className="d-flex align-items-start juctify-content-start">
                    <button
                      type="button"
                      className="btn btn-outline-primary w-100 hover-btn m-0 p-0 border-0 d-flex align-items-center juctify-content-start"
                    >
                      <i
                        className="fas fa-chevron-right mr-1 me-2"
                        aria-hidden="true"
                      ></i>
                      {TR(lang, "help.forCollab")}
                    </button>
                    </li>
                    <li className="d-flex align-items-start juctify-content-start">
                    <button
                      type="button"
                      className="btn btn-outline-primary w-100 hover-btn m-0 p-0 border-0 d-flex align-items-center juctify-content-start"
                    >
                      <i
                        className="fas fa-chevron-right mr-1 me-2"
                        aria-hidden="true"
                      ></i>
                      {TR(lang, "help.forClient")}
                    </button>
                    </li>
                  </ul>
                </div>
                <button
                  style={{padding: '8px 25px'}}
                  onClick={() => setShow(true)}
                  type="button"
                  className="btn btn-primary media-btn d-flex align-items-center juctify-content-center mx-2 rounded-2"
                >
                  <i
                    className=" fas fa-regular fa-address-book me-2"
                    aria-hidden="true"
                  ></i>
                  <span> {TR(lang, 'content.contact')}</span>
                 
                </button>

                <Dropdown
                  as="li"
                  className="nav-item dropdown notification_dropdown"
                >
                  <Dropdown.Toggle
                    className="nav-link i-false c-pointer"
                    variant=""
                    as="a"
                    data-toggle="dropdown"
                    aria-expanded="false"
                  >
                    {lang === "eng" ? (
                      <EngFlag />
                    ) : lang === "ўзб" ? (
                      <UzFlag />
                    ) : (
                      <RuFlag />
                    )}
                  </Dropdown.Toggle>
                  <Dropdown.Menu>
                    <Dropdown.Item
                      className="text-black d-flex align-items-center"
                      onClick={() => handleLangChange("eng")}
                    >
                      <EngFlag /> <span className="ms-2">EN</span>
                    </Dropdown.Item>
                    <Dropdown.Item
                      className="text-black d-flex align-items-center"
                      onClick={() => handleLangChange("ўзб")}
                    >
                      <UzFlag /> <span className="ms-2">UZ</span>
                    </Dropdown.Item>
                    <Dropdown.Item
                      className="text-black d-flex align-items-center"
                      onClick={() => handleLangChange("рус")}
                    >
                      <RuFlag />
                      <span className="ms-2">RUS</span>
                    </Dropdown.Item>
                  </Dropdown.Menu>
                </Dropdown>
              </ul>
              <SupportModal
                show={show}
                setShow={setShow}
              />
            </div>
          </nav>
        </div>
      </div>
    </>
  );
};
const mapStateToProps = (state) => {
  return {
    lang: state.language.lang,
  };
};

export default withRouter(connect(mapStateToProps)(Header));
