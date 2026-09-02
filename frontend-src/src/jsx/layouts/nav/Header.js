import React, { useState } from "react";
import { withRouter } from "react-router-dom";
import { Dropdown } from "react-bootstrap";
import { connect, useDispatch } from "react-redux";
import { ReactComponent as EngFlag } from "../../../icons/flags/eng.svg";
import { ReactComponent as RuFlag } from "../../../icons/flags/rus.svg";
import { ReactComponent as UzFlag } from "../../../icons/flags/uzb.svg";
import { handleLanguageAction } from "../../../store/actions/LanguageActions";
import { logout } from "../../../store/actions/AuthActions";
import { TR } from "../../../utils/helpers";
import { checkRole } from "../../../utils";
import SupportModal from "../../components/Modals/SupportModal";
import ChangePasswordModal from "../../components/Modals/ChangePasswordModal";

const LANGS = [
  { code: "рус", label: "RU", Flag: RuFlag },
  { code: "eng", label: "EN", Flag: EngFlag },
  { code: "ўзб", label: "UZ", Flag: UzFlag },
];

const initialsOf = (u) => {
  const a = (u.first_name || "").trim()[0] || "";
  const b = (u.last_name || "").trim()[0] || "";
  return (a + b).toUpperCase() || (u.email || "?")[0].toUpperCase();
};

/** Top brand bar: data freshness on the left, help / contact / language / profile on the right. */
const Header = ({ lang, userInfo, role, lastUpdateDate, history }) => {
  const dispatch = useDispatch();
  const [support, setSupport] = useState(false);
  const [pass, setPass] = useState(false);
  const user = userInfo || {};
  const current = LANGS.find((l) => l.code === lang) || LANGS[0];
  const fullName = `${user.first_name || ""} ${user.last_name || ""}`.trim() || user.email || "";

  return (
    <>
      <div className="header">
        <div className="header-content">
          <div className="hdr-left">
            {lastUpdateDate ? (
              <span className="hdr-status">
                <i className="fas fa-database" aria-hidden="true" />
                <span>{TR(lang, "content.inputOfLastUpdate")}: <b>{lastUpdateDate}</b></span>
              </span>
            ) : null}
          </div>

          <div className="hdr-right">
            <Dropdown className="hdr-dd" align="end">
              <Dropdown.Toggle as="button" type="button" className="hdr-btn">
                <i className="fas fa-question-circle" aria-hidden="true" />
                <span>{TR(lang, "sidebar.Help")}</span>
              </Dropdown.Toggle>
              <Dropdown.Menu className="hdr-menu">
                <Dropdown.Item as="button" type="button"><i className="fas fa-user-shield" />{TR(lang, "help.forAdmin")}</Dropdown.Item>
                <Dropdown.Item as="button" type="button"><i className="fas fa-user-friends" />{TR(lang, "help.forCollab")}</Dropdown.Item>
                <Dropdown.Item as="button" type="button"><i className="fas fa-user" />{TR(lang, "help.forClient")}</Dropdown.Item>
              </Dropdown.Menu>
            </Dropdown>

            <button type="button" className="hdr-btn" onClick={() => setSupport(true)}>
              <i className="fas fa-envelope" aria-hidden="true" />
              <span>{TR(lang, "content.contact")}</span>
            </button>

            <Dropdown className="hdr-dd" align="end">
              <Dropdown.Toggle as="button" type="button" className="hdr-btn hdr-lang" aria-label="Language">
                <current.Flag />
                <span>{current.label}</span>
              </Dropdown.Toggle>
              <Dropdown.Menu className="hdr-menu">
                {LANGS.map(({ code, label, Flag }) => (
                  <Dropdown.Item as="button" type="button" key={code} className={code === lang ? "active" : ""} onClick={() => dispatch(handleLanguageAction(code))}>
                    <Flag /> {label}
                  </Dropdown.Item>
                ))}
              </Dropdown.Menu>
            </Dropdown>

            <Dropdown className="hdr-dd" align="end">
              <Dropdown.Toggle as="button" type="button" className="hdr-user">
                <span className="hdr-avatar">{initialsOf(user)}</span>
                <span className="hdr-user-meta">
                  <span className="n">{fullName}</span>
                  {user.email && fullName !== user.email ? <span className="r">{user.email}</span> : null}
                </span>
                <i className="fas fa-chevron-down" aria-hidden="true" />
              </Dropdown.Toggle>
              <Dropdown.Menu className="hdr-menu hdr-menu-user">
                <div className="hdr-menu-head">
                  <div className="n">{fullName}</div>
                  {user.email ? <div className="r">{user.email}</div> : null}
                </div>
                <Dropdown.Item as="button" type="button" onClick={() => setPass(true)}>
                  <i className="fas fa-key" />{TR(lang, "auth.changePass")}
                </Dropdown.Item>
                {checkRole("1", role) ? (
                  <Dropdown.Item as="button" type="button" onClick={() => history.push("/profile/settings")}>
                    <i className="fas fa-cog" />{TR(lang, "content.settings")}
                  </Dropdown.Item>
                ) : null}
                <Dropdown.Divider />
                <Dropdown.Item as="button" type="button" className="danger" onClick={() => dispatch(logout(history))}>
                  <i className="fas fa-sign-out-alt" />{TR(lang, "navBar.logOut")}
                </Dropdown.Item>
              </Dropdown.Menu>
            </Dropdown>
          </div>
        </div>
      </div>

      <SupportModal show={support} setShow={setSupport} />
      <ChangePasswordModal show={pass} setShow={setPass} />
    </>
  );
};

const mapStateToProps = (state) => ({
  lang: state.language.lang,
  userInfo: state.main.userInfo,
  role: state.main.userInfo ? state.main.userInfo.user_role : null,
  lastUpdateDate: state.main.lastUpdateDate,
});

export default withRouter(connect(mapStateToProps)(Header));
