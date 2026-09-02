import { connect } from "react-redux"
import { TR } from "../../../utils/helpers";
import User from '../../../services/cruds/UserService'
import {useEffect, useState} from "react";
import { Button, Modal } from "react-bootstrap";
import { showToast } from "../../../utils";

function ChangeAccessModal(props) {
    const {lang, show, setShow, user, getAllList, productTypes} = props;
    const [list, setList] = useState(productTypes || []);
    const [selectedVip, setSelectedVip] = useState(false);
    useEffect(() => {
        const ids = user.access.map(e => e.type_id);
        const temp = productTypes.map(e => ({
            ...e,
            check: ids.includes(e.value)
        }))
        setList([...temp])
        setSelectedVip(temp.length === temp.filter(e => e.check).length)
    },[user])
    const handleChangeSubmit = (e) => {
        e.preventDefault()
        const data = list.filter(e => e.check).map(e => ({type_id: e.value}))
        User.changeAccess(user.id, {access: data}).then(res => {
            setShow(false);
            getAllList()
            showToast('success', res.data.message);
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
    }

    return <Modal show={show} onHide={setShow}>
        <Modal.Header className="c-header" closeButton>
            <Modal.Title>{TR(lang, 'access')}</Modal.Title>

        </Modal.Header>
        <Modal.Body className="pb-0">
            <div className="d-flex">
                <input
                    onChange={() => {
                        if(selectedVip){
                            setList(data => [...data.map(e => ({...e, check: false}))]);
                            setSelectedVip(false);
                        } else {
                            setList(data => [...data.map(e => ({...e, check: true}))]);
                            setSelectedVip(true);
                        }

                    }}
                    id="select_vip"
                    checked={selectedVip}
                    type={"checkbox"}
                />
                <label htmlFor="select_vip" className="mx-3">{TR(lang, "select_vip")}</label>
            </div>
            {
                list && list.map((e, index) => (
                    <div className="d-flex ms-5">
                        <input
                            id={e.value}
                            onChange={(event) => {
                                const temp = [...list];
                                temp[index].check = event.target.checked;
                                setList([...temp]);
                                setSelectedVip(temp.length === temp.filter(e => e.check).length);
                            }}
                            checked={e.check}
                            type={"checkbox"}
                        />
                        <label htmlFor={e.value} className="mx-3">{e.label}</label>
                    </div>
                ))
            }
        </Modal.Body>
        <Modal.Footer className="py-2">
            <Button variant="danger" onClick={() => setShow(false)}>
                {TR(lang, 'content.cancel')}
            </Button>
            <Button onClick={handleChangeSubmit}>
                {TR(lang, 'content.save')}
            </Button>
        </Modal.Footer>
    </Modal>
}

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        productTypes: state.main.productTypes,
    };
};

export default connect(mapStateToProps)(ChangeAccessModal);