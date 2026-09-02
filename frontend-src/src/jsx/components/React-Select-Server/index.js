import React, { useState } from 'react';
import { connect } from 'react-redux';

import ReactSelect from 'react-select';
import { TR } from '../../../utils/helpers';
const noop = () => { };
function SelectWithServer(props) {
    const [value, setValue] = useState("");
    const [selectRef, setSelectRef] = useState(null);
    const { filterDb, lang, arr_key, API, index, additional } = props;
    const { isDisabled, styles, required, className } = props;
    const enableRequired = !isDisabled;
    const onChange = (e) => {
        props.onChange(e);
        setValue(e ? e.value : "")
    };

    const getValue = () => {
        if (props.value !== undefined) return props.value;
        return value || "";
    };
    const handleInputChange = (newValue) => {
        setValue(newValue)
        if (newValue.length > 2) {
            filterDb(arr_key, API, newValue, isNaN(index) ? null : index, additional);
        }
        return newValue;
    };
    const NoOptionsMessage = () => {
        if (value && value.length < 3) {
            return <p className="text-center m-0">
                {TR(lang, "select.minimum")}
            </p>;
        } else {
            return <p className="text-center m-0">
                {TR(lang, "select.noData")}
            </p>;
        }
    };
    return (
        <div className={className} style={styles}>
            <ReactSelect
                placeholder={`${TR(lang, "content.select")} ...`}
                {...props}
                ref={(e) => setSelectRef(e)}
                components={{ NoOptionsMessage: NoOptionsMessage }}
                onInputChange={handleInputChange}
                onChange={onChange}
            />
            {enableRequired && (
                <input
                    tabIndex={-1}
                    autoComplete="off"
                    style={{
                        opacity: 0,
                        height: 0,
                        position: "absolute",
                        padding: '5px 20px',
                        color: '#000'
                    }}
                    value={getValue()}
                    onChange={noop}
                    onFocus={() => selectRef.focus()}
                    required={required}
                />)
            }
        </div>
    );

}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(SelectWithServer);