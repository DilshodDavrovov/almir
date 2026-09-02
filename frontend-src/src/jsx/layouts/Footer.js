import React from "react";
import { connect } from "react-redux";
import { TR } from "../../utils/helpers";

const Footer = ({ settingsData, lang }) => {
  const s = settingsData || {};
  return (
    <div className="footer">
      <div className="copyright">
        <span className="ft-brand">© {new Date().getFullYear()} ALMIR STATISTICS</span>
        <span className="ft-contacts">
          {s.contact_fax ? <span><i className="fas fa-fax" aria-hidden="true" />{TR(lang, "reg.fax")}: {s.contact_fax}</span> : null}
          {s.contact_email ? <span><i className="fas fa-envelope" aria-hidden="true" />{s.contact_email}</span> : null}
          {s.contact_phone ? <span><i className="fas fa-phone-alt" aria-hidden="true" />{s.contact_phone}</span> : null}
        </span>
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
