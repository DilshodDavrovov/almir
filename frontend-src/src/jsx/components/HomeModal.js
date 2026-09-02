import React, { useState } from "react";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { autoCorrectedDatePipe, formatDateToDay, stringToDate } from "../../utils";
import ReactDatePicker from "react-datepicker";
import MaskedInput from "react-text-mask";
import { connect } from "react-redux";
import { TR } from "../../utils/helpers";
const HomeModal = (props) => {
    const { show, setShow, handleChartSubmit, date, lang} = props;
    const [thisDate, setThisDate] = useState(date)
    return (
        <Modal show={show} onHide={setShow}>
            <Modal.Header className="c-header" closeButton>
                <Modal.Title>{TR(lang, 'home.period')}</Modal.Title>
            </Modal.Header>
            <Modal.Body className="pb-0">
                <form 
                    className="row" 
                    onSubmit={(e) =>{
                        e.preventDefault();
                        handleChartSubmit(thisDate);
                    }}
                >
                    <label>
                        {TR(lang, 'home.period')}
                    </label>
                    <div className="mb-3 col-md-6">
                        <ReactDatePicker
                            id="exampleFormControlInput1"
                            showYearDropdown
                            showMonthDropdown
                            dropdownMode="select"
                            className="form-control form-control-sm"
                            onSelect = {e => setThisDate(key => ({...key, fromDate: formatDateToDay(e)}))}
                            onChange = {e => setThisDate(key => ({...key, fromDate: formatDateToDay(e)}))}
                            maxDate = {thisDate.toDate ? stringToDate(thisDate.toDate, 'dd-mm-yyyy', '-'):null}
                            selected = {thisDate.fromDate?stringToDate(thisDate.fromDate, 'dd-mm-yyyy', '-'):null}
                            customInput = {<MaskedInput
                                pipe={autoCorrectedDatePipe}
                                mask={[/\d/, /\d/, '/', /\d/, /\d/, '/', /\d/, /\d/, /\d/, /\d/]}
                                keepCharPositions= {true}
                                guide = {true}
                            />}
                            placeholderText='__/__/____'
                            dateFormat='dd/MM/yyyy'
                            required
                        />
                    </div>
                    <div className="mb-3 col-md-6">
                        <ReactDatePicker
                            id="exampleFormControlInput2"
                            showYearDropdown
                            showMonthDropdown
                            dropdownMode="select"
                            className="form-control form-control-sm"
                            onSelect = {e => setThisDate(key => ({...key, toDate: formatDateToDay(e)}))}
                            onChange = {e => setThisDate(key => ({...key, toDate: formatDateToDay(e)}))}
                            minDate = {thisDate.fromDate?stringToDate(thisDate.fromDate, 'dd-mm-yyyy', '-'):null}
                            selected = {thisDate.toDate ? stringToDate(thisDate.toDate, 'dd-mm-yyyy', '-'):null}
                            customInput = {<MaskedInput
                                pipe={autoCorrectedDatePipe}
                                mask={[/\d/, /\d/, '/', /\d/, /\d/, '/', /\d/, /\d/, /\d/, /\d/]}
                                keepCharPositions= {true}
                                guide = {true}
                            />}
                            placeholderText='__/__/____'
                            dateFormat='dd/MM/yyyy'
                            required
                        />
                    </div>
                    <Modal.Footer className="py-2">
                        <Button variant="danger" onClick={() => setShow(false)}>
                            {TR(lang, "content.cancel")}
                        </Button>
                        <Button type="submit" variant="primary">
                            {TR(lang, "content.search")}
                        </Button>
                    </Modal.Footer>
                </form>
            </Modal.Body>
        </Modal>
    );
};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(HomeModal);
