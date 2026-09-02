import React from 'react';
import { Modal } from 'react-bootstrap';
import { TR } from './../../../utils/helpers';
import { connect, useDispatch } from 'react-redux';
import MaskedInput from 'react-text-mask';
import ReactDatePicker from 'react-datepicker';
import { ParseDateToString, autoCorrectedDatePipe, showToast, stringToDate } from '../../../utils';
import { useState } from 'react';
import API from '../../../services/HomeService'
import { setLastDate } from '../../../store/actions/MainAction';
 

function UpdateLogModal(props) {
    const dispatch = useDispatch();
    const { updateLogModal, setUpdateLogModal, lang, lastUpdateDate } = props;
    const [date, setDate] = useState(stringToDate(lastUpdateDate, 'dd-mm-yyyy', '-'));
    const update = (e) => {
        e.preventDefault();
        API.updateLog(ParseDateToString(date)).then(res => {
            setUpdateLogModal(false);
            dispatch(setLastDate(res.data.data.updated_date));
            showToast('success', res.data.message);
        }).catch(err => {
            showToast('error', err.response.data.message);
        });
    }
    return (
        <Modal className="modal fade" show={updateLogModal} onHide={setUpdateLogModal} >
            <form onSubmit={e => update(e)}>
                <div className="modal-header">
                    <h4 className="modal-title fs-20">{TR(lang, "content.dateofupdate")}</h4>
                    <button type="button" className="btn-close" onClick={() => setUpdateLogModal(false)} data-dismiss="modal"><span></span></button>
                </div>
                <div className="modal-body">
                    <i className="flaticon-cancel-12 close" data-dismiss="modal"></i>
                    <div className="form-group mb-3">
                        <label className="text-black font-w500" htmlFor="c_code">{TR(lang, "content.inputOfLastUpdate")}</label>
                        <div className="contact-name">
                            <ReactDatePicker
                                showYearDropdown
                                showMonthDropdown
                                dropdownMode="select"
                                className="form-control form-control-sm"
                                onSelect={e => setDate(e)}
                                onChange={e => setDate(e)}
                                selected={date}
                                customInput={<MaskedInput
                                    pipe={autoCorrectedDatePipe}
                                    mask={[/\d/, /\d/, '/', /\d/, /\d/, '/', /\d/, /\d/, /\d/, /\d/]}
                                    keepCharPositions={true}
                                    guide={true}
                                />}
                                placeholderText='__/__/____'
                                dateFormat='dd/MM/yyyy'
                                required
                            />
                        </div>
                    </div>
                </div>
                <div className="modal-footer">
                    <button onClick={() => setUpdateLogModal(false)} className={`btn btn-primary`}>{TR(lang, "content.cancel")}</button>
                    <button type="submit" className="btn btn-primary">{TR(lang, "content.save")}</button>
                </div>
            </form>

        </Modal>
    );
}

const mapStateToProps = (state) => {
    return {
        lastUpdateDate: state.main.lastUpdateDate,
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(UpdateLogModal);