import React, { useState, useContext } from 'react';
import ServerSelect from '../../../components/React-Select-Server';
import { TR } from "../../../../utils/helpers";
import { connect, useDispatch } from 'react-redux';
import { drugAddDataAction, drugAddSelectAction, drugAddSelectLoadingAction } from '../../../../store/actions/DrugsAction';
import DForm from '../../../../services/cruds/DFormService';
import DFarmGroup from '../../../../services/cruds/DFarmGroupService';
import TGroup from '../../../../services/cruds/TGroupService';
import DType from '../../../../services/cruds/DTypesService';
import Inn from '../../../../services/cruds/InnService';
import TradeMark from '../../../../services/cruds/TradeMarkService';
import Manufacturer from '../../../../services/cruds/ManufacturerService';
import Drug from '../../../../services/cruds/DrugsService';
import { useHistory } from 'react-router-dom';
import { showToast } from '../../../../utils';
function AddDrugs(props) {
    const dispatch = useDispatch();
    const history = useHistory();
    const priceOptions = [{ value: "USD", label: "$" }, { value: "EUR", label: "€" }, { value: "RUB", label: "₽" }, { value: "UZS", label: "SO'M" },];
    const { setLoading, filter, lang, data, list, listLoading } = props;
    const [count, setCount] = useState([0]);
    const clearData = () => {
        setCount([0]);
        dispatch(drugAddDataAction({
            name: "",
            ref_price: 1,
            ref_price_ccy: "UZS",
            di_id: "",
            df_id: "",
            dfg_id: "",
            dtg_id: "",
            dt_id: "",
            trademark_id: "",
            is_rx: false,
            is_otc: false,
            is_active: true,
            deleted: false,
            manufacturers: [{ manufacturer_id: "", drug_mxik: "" }]
        }));
        dispatch(drugAddSelectAction({
            dForm: [],
            inn: [],
            dType: [],
            dFarmGroup: [],
            tGroup: [],
            tradeMark: [],
            manufacturer: [[]]
        }));
        dispatch(drugAddSelectLoadingAction({
            dForm: false,
            inn: false,
            dType: false,
            dFarmGroup: false,
            tGroup: false,
            tradeMark: false,
            manufacturer: [false]
        }));
    }
    const addDrug = e => {
        e.preventDefault();
        Drug.save(data).then(res => {
            if (res.status === 200) {
                showToast('success', res.data.message);
                setLoading(true);
                filter();
                clearData();
                history.push('/admin/drugs');
            }
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
    };
    const handleLoading = (arr_key, value) => {
        listLoading[arr_key] = value;
        dispatch(drugAddSelectLoadingAction(listLoading));
    }
    const handleArrLoading = (arr_key, value, index) => {
        listLoading[arr_key][index] = value;
        dispatch(drugAddSelectLoadingAction(listLoading));
    }
    const filterDb = (arr_key, API, value, index) => {
        if (index === null) {
            handleLoading(arr_key, true)
            API.select(true, false, value).then((res) => {
                list[arr_key] = [...res.data.data.map(key => ({
                    value: key.id,
                    label: key.full_name
                }))]
                dispatch(drugAddSelectAction(list));
                handleLoading(arr_key, false)
            })
        } else {

            handleArrLoading(arr_key, true, index);
            API.select(true, false, value).then((res) => {
                list[arr_key][index] = [...res.data.data.map(key => ({
                    value: key.id,
                    label: key.full_name
                }))]

                dispatch(drugAddSelectAction(list));
                handleArrLoading(arr_key, false, index)
            })
        }
    };
    const handleSetManufacturer = (key, value, index) => {
        if (!data.manufacturers[index]) data.manufacturers[index] = { manufacturer_id: "", drug_mxik: "" };
        data.manufacturers[index][key] = value;
        dispatch(drugAddDataAction({ ...data }))
    }
    function handleDelete(e, index) {
        e.preventDefault();
        if (count.length === 1) return;
        data.manufacturers.splice(index, 1);
        list.manufacturer.splice(index, 1);
        listLoading.manufacturer.splice(index, 1);
        setCount(count.splice(0, count.length - 1));
        dispatch(drugAddDataAction({ ...data }));
        dispatch(drugAddSelectAction({ ...list }));
        dispatch(drugAddSelectLoadingAction({ ...listLoading }));
    }
    function handleAdd(e) {
        e.preventDefault();
        data.manufacturers.push({ manufacturer_id: "", drug_mxik: "" });
        list.manufacturer.push([]);
        listLoading.manufacturer.push(false);
        setCount([...count, count.length]);
        dispatch(drugAddDataAction({ ...data }));
        dispatch(drugAddSelectAction({ ...list }));
        dispatch(drugAddSelectLoadingAction({ ...listLoading }));
    }
    return <div className="card">
        <div className="card-header">
            <h4 className="card-title">{TR(lang, "content.adding")} {TR(lang, "cruds.med")} {TR(lang, "cruds.add")}</h4>
        </div>
        <div className="card-body">
            <div className="basic-form">
                <form onSubmit={(e) => addDrug(e)}>
                    <div className="row d-flex align-items-center">
                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "table.name")}</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => dispatch(drugAddDataAction({ ...data, name: e.target.value }))}
                                className="form-control"
                                placeholder={TR(lang, "table.name")}

                            />

                        </div>
                        <div className='col-md-6 row'>
                            <div className="form-check col-xl-2 col-md-6 col-sm-6  d-flex align-items-center mx-2">
                                <input
                                    className="form-check-input"
                                    type="radio"
                                    id="flexRadioDefault1"
                                    onClick={() => dispatch(drugAddDataAction({ ...data, is_rx: !data.is_rx, is_otc: data.is_rx }))}
                                    checked={data.is_rx}
                                />
                                <label className="form-check-label mx-2 my-0" htmlFor="flexRadioDefault1"> Rx </label>
                            </div>
                            <div className="form-check col-xl-2 col-md-6 col-sm-6  d-flex align-items-center mx-2">
                                <input
                                    className="form-check-input"
                                    type="radio"
                                    id="flexRadioDefault2"
                                    onClick={() => dispatch(drugAddDataAction({ ...data, is_otc: !data.is_otc, is_rx: data.is_otc }))}
                                    checked={data.is_otc}
                                />
                                <label className="form-check-label mx-2 my-0" htmlFor="flexRadioDefault2">  OTC </label>
                            </div>
                        </div>
                        {/* <div className="form-group mb-3 col-md-6">
                        <label>{TR(lang, "table.name")}</label>
                        <div className="input-group" >
                            <input
                                value={data.ref_price}
                                onChange={(e) => dispatch(drugAddDataAction({...data, ref_price: e.target.value}))}
                                className="form-control" 
                                aria-label="Text input with dropdown button" 
                                />
                            <select
                                defaultValue={"option"}
                                className="form-select"
                                value={data.ref_price_ccy}
                                onChange={(e) => dispatch(drugAddDataAction({...data, ref_price_ccy: e.target.value}))}
                            >
                                {priceOptions.map(ccy => {
                                    return <option key = {ccy.value} value={ccy.value}>{ccy.label}</option>
                                })}
                            </select>
                        </div>
                        </div> */}

                    </div>
                    <div className="row">
                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "products.mnn")}</label>
                            <ServerSelect
                                API={Inn}
                                arr_key='inn'
                                options={list.inn}
                                onChange={e => dispatch(drugAddDataAction({ ...data, di_id: e.value }))}
                                value={list.inn.filter(key => key.value === data.di_id)}
                                isLoading={listLoading.inn}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.mnn")}
                                required
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "products.df")}</label>
                            <ServerSelect
                                API={DForm}
                                arr_key='dForm'
                                options={list.dForm}
                                onChange={e => dispatch(drugAddDataAction({ ...data, df_id: e.value }))}
                                value={list.dForm.filter(key => key.value === data.df_id)}
                                isLoading={listLoading.dForm}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.df")}
                                required
                            />
                        </div>

                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "products.dfg")}</label>
                            <ServerSelect
                                API={DFarmGroup}
                                arr_key='dFarmGroup'
                                options={list.dFarmGroup}
                                onChange={e => dispatch(drugAddDataAction({ ...data, dfg_id: e.value }))}
                                value={list.dFarmGroup.filter(key => key.value === data.dfg_id)}
                                isLoading={listLoading.dFarmGroup}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.dfg")}
                                required
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "products.tpg")}</label>
                            <ServerSelect
                                API={TGroup}
                                arr_key='tGroup'
                                options={list.tGroup}
                                onChange={e => dispatch(drugAddDataAction({ ...data, dtg_id: e.value }))}
                                value={list.tGroup.filter(key => key.value === data.dtg_id)}
                                isLoading={listLoading.tGroup}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.tpg")}
                                required
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "products.dt")}</label>
                            <ServerSelect
                                style={{ padding: '5px 20px' }}
                                API={DType}
                                arr_key='dType'
                                options={list.dType}
                                onChange={e => dispatch(drugAddDataAction({ ...data, dt_id: e.value }))}
                                value={list.dType.filter(key => key.value === data.dt_id)}
                                isLoading={listLoading.dType}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.dt")}
                                required
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label>{TR(lang, "products.td")}</label>
                            <ServerSelect
                                API={TradeMark}
                                arr_key='tradeMark'
                                options={list.tradeMark}
                                onChange={e => dispatch(drugAddDataAction({ ...data, trademark_id: e.value }))}
                                value={list.tradeMark.filter(key => key.value === data.trademark_id)}
                                isLoading={listLoading.tradeMark}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.td")}
                                required
                            />
                        </div>
                    </div>
                    {count.map(index => {
                        return (
                            <div className='row align-items-end'>
                                <div className='col-md-9 row'>
                                    <div className='form-group col-md-8'>
                                        <label>{TR(lang, "table.mf")}</label>
                                        <ServerSelect
                                            API={Manufacturer}
                                            index={index}
                                            arr_key='manufacturer'
                                            options={list.manufacturer[index]}
                                            onChange={e => handleSetManufacturer('manufacturer_id', e.value, index)}
                                            value={list.manufacturer[index].filter(key => key.value === data.manufacturers[index].manufacturer_id)}
                                            isLoading={listLoading.manufacturer[index]}
                                            filterDb={filterDb}
                                            placeholder={TR(lang, "products.mf")}
                                            required
                                        />
                                    </div>
                                    <div className="form-group mb-3 col-md-4">
                                        <label className='black-font' htmlFor='mxik'>{TR(lang, "products.mxik")}</label>
                                        <input
                                            className='form-control'
                                            id='mxik'
                                            type='text'
                                            placeholder={TR(lang, "products.mxik")}
                                            onChange={e => handleSetManufacturer('drug_mxik', e.target.value, index)}
                                            value={data.manufacturers[index].drug_mxik}
                                        />
                                    </div>
                                </div>
                                <div className='col-md-3'>
                                    {(count.length !== 1) && <button className={`btn btn-sm btn-primary ${document.body.clientWidth > 576 ? 'mr-3' : 'mr-1'}`} onClick={(e) => { handleDelete(e, index) }}><i className='fa fa-minus'></i></button>}
                                    {(index + 1 === count.length) && <button className="btn btn-sm btn-success" onClick={(e) => { handleAdd(e) }}><i className='fa fa-plus'></i></button>}
                                </div>
                            </div>
                        )
                    })
                    }
                    <div className='d-flex my-2 float-end'>
                        <button onClick={(e) => { e.preventDefault(); clearData() }} className="btn btn-warning">{TR(lang, "content.resetAll")}</button>
                        <button type='submit' className="ms-3 btn btn-primary">{TR(lang, "content.save")}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        data: state.drugs.data,
        list: state.drugs.list,
        listLoading: state.drugs.listLoading,
    };
};

export default connect(mapStateToProps)(AddDrugs);