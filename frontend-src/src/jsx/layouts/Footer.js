import React from "react";
import { connect } from "react-redux";
import { TR } from "../../utils/helpers";

const Footer = ({settingsData, lang}) => {
  return (
    <div className="footer">
      <div className="copyright text-center">
        {
            settingsData.contact_fax ? 
            <span className="text-black mx-2">
                {TR(lang, "reg.fax")}: {settingsData.contact_fax}
            </span>: null
        }
        {
            settingsData.contact_email ? 
            <span className="text-black mx-2">
                {TR(lang, "auth.email")}: {settingsData.contact_email}
            </span>: null
        }
        {
            settingsData.contact_phone ? 
            <span className="text-black mx-2">
                {TR(lang, "reg.phone")}: {settingsData.contact_phone}
            </span>: null
        }
      </div>
    </div>
  );
};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        settingsData: state.main.settingsData,
    };
};

export default connect(mapStateToProps)(Footer);

