import React from 'react';

import Select from 'react-select';
import {connect} from "react-redux";
import {TR} from "../../../utils/helpers";

function MultiSelect(props){
    const { styles, lang, placeholder} = props;
    return (
        <div style={styles}>
            <Select
                {...props}
                placeholder={`${TR(lang, placeholder)} ...`}
                defaultValue={[]}
                isMulti
            />
        </div>

)
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(MultiSelect);