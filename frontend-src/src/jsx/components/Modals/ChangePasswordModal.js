import { connect } from "react-redux"
import { TR } from "../../../utils/helpers";
import User from '../../../services/cruds/UserService'
import { useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { showToast } from "../../../utils";

function ChangePasswodModal(props) {
    const {lang, userInfo, userId, isChangeOther, show, setShow} = props;
    const [password, setPassword] = useState('');
    const [eye, setEye] = useState(false);
    const [confirmPassword, setConfirmPassword] = useState('');
    const handleChangeSubmit = (e) => {
        e.preventDefault()
        if(password !== confirmPassword){
            showToast('error', TR(lang, "auth.not_equal"));
        } else {
            User.changePass(isChangeOther? userId.id : userInfo.id, password).then(res => {
                setShow(false);
                showToast('success', res.data.message);
            }).catch(err => {
                showToast('error', err.response.data.message);
            })
        }

    }

    return <Modal show={show} onHide={setShow}>
        <Modal.Header className="c-header" closeButton>
            <Modal.Title>{TR(lang, 'auth.changePass')}</Modal.Title>

        </Modal.Header>
        <Modal.Body className="pb-0">

            <form className="row" onSubmit={handleChangeSubmit}>
                <div className="mb-3 col-md-12">
                    <label htmlFor="changePass" className="form-label">
                        {TR(lang, 'auth.password')}
                    </label>
                    <div className="d-flex justify-content-between">
                        <input
                            required
                            type = {`${(eye)? 'text' : 'password'}`}
                            onChange={e => setPassword(e.target.value)}
                            value={password}
                            className="form-control"
                            id="changePass"
                            placeholder={TR(lang, 'auth.password')}
                        />
                        <i style={{marginLeft: "-30px", marginRight: "10px"}} className={`d-flex align-items-center far ${(eye)?  'fa-eye' : 'fa-eye-slash'}`} onClick={() => setEye(!eye)}></i>
                    </div>
                </div>
                <div className="mb-3 col-md-12">
                    <label htmlFor="changeConfPass" className="form-label">
                        {TR(lang, 'reg.confirmPass')}
                    </label>
                    <div className="d-flex justify-content-between">
                        <input
                            required
                            type = {`${(eye)? 'text' : 'password'}`}
                            value={confirmPassword}
                            onChange={e => setConfirmPassword(e.target.value)}
                            className="form-control"
                            id="changeConfPass"
                            placeholder={TR(lang, 'reg.confirmPass')}
                        />
                        <i style={{marginLeft: "-30px", marginRight: "10px"}} className={`d-flex align-items-center far ${(eye)?  'fa-eye' : 'fa-eye-slash'}`} onClick={() => setEye(!eye)}></i>
                    </div>
                </div>
                <Modal.Footer className="py-2">
                    <Button variant="danger" onClick={() => setShow(false)}>
                        {TR(lang, 'content.cancel')}
                    </Button>
                    <Button type="submit">
                        {TR(lang, 'content.save')}
                    </Button>
                </Modal.Footer>

            </form>
        </Modal.Body>

    </Modal>
}

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        userInfo: state.main.userInfo
    };
};

export default connect(mapStateToProps)(ChangePasswodModal);