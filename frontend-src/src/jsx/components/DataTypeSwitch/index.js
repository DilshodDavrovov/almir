import React from "react";
import { connect } from "react-redux";
import { TR } from "../../../utils/helpers";
import { checkRole } from "../../../utils";
import "./dataTypeSwitch.css";

/**
 * "Приход / Продажа" segmented switch shown in a report's header.
 * Shared by Analytics, Pivot, Analyze (comparative analysis) and the advanced search
 * report — same control, same visibility rule everywhere: only users who may choose
 * (admin, or an account with shipment_access) see it; everyone else keeps whatever
 * data type the report defaults to.
 *
 * props: value (1 = Приход, 2 = Продажа), onChange(nextValue), disabled
 */
const DataTypeSwitch = ({ value, onChange, disabled, lang, role, shipment_access }) => {
    if (!(checkRole(1, role) || shipment_access)) return null;
    return (
        <div className="data-type-switch" role="group" aria-label={TR(lang, "products.analysis_type")}>
            <button
                type="button"
                className={value === 1 ? "active" : ""}
                disabled={disabled}
                onClick={() => value !== 1 && onChange(1)}
            >
                {TR(lang, "analytics.prixod")}
            </button>
            <button
                type="button"
                className={value === 2 ? "active" : ""}
                disabled={disabled}
                onClick={() => value !== 2 && onChange(2)}
            >
                {TR(lang, "analytics.prodaja")}
            </button>
        </div>
    );
};

const mapStateToProps = (state) => ({
    lang: state.language.lang,
    role: state.main.userInfo ? state.main.userInfo.user_role : null,
    shipment_access: state.main.userInfo ? state.main.userInfo.shipment_access : null,
});

export default connect(mapStateToProps)(DataTypeSwitch);
