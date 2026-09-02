import React, { useEffect, useState } from "react";
import ServerSelect from '../../../components/React-Select-Server';
import { TR } from "../../../../utils/helpers";
import DForm from '../../../../services/cruds/DFormService';
import DFarmGroup from '../../../../services/cruds/DFarmGroupService';
import TGroup from '../../../../services/cruds/TGroupService';
import DType from '../../../../services/cruds/DTypesService';
import Inn from '../../../../services/cruds/InnService';
import TradeMark from '../../../../services/cruds/TradeMarkService';
import Manufacturer from '../../../../services/cruds/ManufacturerService';
import Drug from '../../../../services/cruds/DrugsService';
import { drugEditDataAction, drugEditSelectAction, drugEditSelectLoadingAction } from '../../../../store/actions/DrugsAction';
import { useHistory, Link } from "react-router-dom";
import { connect, useDispatch } from "react-redux";
import { NumberToStr, showToast, StrtoNumber } from '../../../../utils'
import Loading from "../../../components/Loading";
function EditDrugs(props) {
    const [drugLoading, setDrugLoading] = useState(true);
    const { id } = props.match.params;
    const history = useHistory();
    const dispatch = useDispatch();
    const priceOptions = [{ value: "USD", label: "$" }, { value: "EUR", label: "€" }, { value: "RUB", label: "₽" }, { value: "UZS", label: "SO'M" },];
    const { loading, setLoading, filter, lang, data, list, listLoading } = props;
    const [count, setCount] = useState([0]);
    const getDrugList = () => {
        setDrugLoading(true);
        Drug.getById(id).then(res => {
            const drug = res.data.data;
            dispatch(drugEditDataAction({
                ...data,
                name: drug.name,
                dt_id: drug._dt?.id,
                di_id: drug.drug_inn?.id,
                df_id: drug.drug_form?.id,
                dfg_id: drug.drug_form_group?.id,
                dtg_id: drug.drug_ts_group?.id,
                ref_price: Number(drug.ref_price),
                ref_price_ccy: drug.ref_price_ccy,
                trademark_id: drug.trademark?.id,
                is_rx: drug.is_rx,
                is_otc: drug.is_otc,
                is_active: drug.is_active,
                deleted: drug.deleted,
                manufacturers: drug._manufacturers
            }))
            dispatch(drugEditSelectAction({
                dForm: [{ value: drug.drug_form?.id, label: drug.drug_form?.name }],
                inn: [{ value: drug.drug_inn?.id, label: drug.drug_inn?.name }],
                dType: [{ value: drug._dt?.id, label: drug._dt?.name }],
                dFarmGroup: [{ value: drug.drug_form_group?.id, label: drug.drug_form_group?.name }],
                tGroup: [{ value: drug.drug_ts_group?.id, label: drug.drug_ts_group?.name }],
                tradeMark: [{ value: drug.trademark?.id, label: drug.trademark?.name }],
                manufacturer: [...drug._manufacturers.map(key => [{ value: key.manufacturer_id, label: key.full_name }])],
            }))
            setCount(Array(Math.ceil(drug._manufacturers.length)).fill().map((_, i) => i));
            setDrugLoading(false);
        })
    };
    useEffect(() => {
        getDrugList();
    }, []);
    const saveEditingDrug = async e => {
        e.preventDefault();
        Drug.edit(id, data).then(res => {
            showToast('success', res.data.message);
            filter();
            setLoading(true);
            history.push('/admin/drugs');
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
    };

    const handleLoading = (arr_key, value) => {
        listLoading[arr_key] = value;
        dispatch(drugEditSelectLoadingAction(listLoading));
    }
    const handleArrLoading = (arr_key, value, index) => {
        listLoading[arr_key][index] = value;
        dispatch(drugEditSelectLoadingAction(listLoading));
    }

    const filterDb = (arr_key, API, value, index) => {
        if (index == null) {
            handleLoading(arr_key, true)
            API.select(true, false, value).then((res) => {
                list[arr_key] = [...res.data.data.map(key => ({
                    value: key.id,
                    label: key.full_name
                }))]
                dispatch(drugEditSelectAction(list));
                handleLoading(arr_key, false)
            })
        } else {
            handleArrLoading(arr_key, true, index);
            API.select(true, false, value).then((res) => {
                list[arr_key][index] = [...res.data.data.map(key => ({
                    value: key.id,
                    label: key.full_name
                }))]
                dispatch(drugEditSelectAction(list));
                handleArrLoading(arr_key, false, index)
            })
        }
    };

    const handleSetManufacturer = (key, value, index) => {
        if (!data.manufacturers[index]) data.manufacturers[index] = { manufacturer_id: "", drug_mxik: "" };
        data.manufacturers[index][key] = value;
        dispatch(drugEditDataAction({ ...data }))
    }
    function handleDelete(e, index) {
        e.preventDefault();
        if (count.length === 1) return;
        data.manufacturers.splice(index, 1);
        list.manufacturer.splice(index, 1);
        listLoading.manufacturer.splice(index, 1);
        setCount(count.splice(0, count.length - 1));
        dispatch(drugEditDataAction({ ...data }));
        dispatch(drugEditSelectAction({ ...list }));
        dispatch(drugEditSelectLoadingAction({ ...listLoading }));
    }
    function handleAdd(e) {
        e.preventDefault();
        data.manufacturers.push({ manufacturer_id: "", drug_mxik: "" });
        list.manufacturer.push([]);
        listLoading.manufacturer.push(false);
        setCount([...count, count.length]);
        dispatch(drugEditDataAction({ ...data }));
        dispatch(drugEditSelectAction({ ...list }));
        dispatch(drugEditSelectLoadingAction({ ...listLoading }));
    }
    return <div className="card">
        <div className="card-header">
            <h4 className="card-title">{TR(lang, "content.editing")} {TR(lang, "cruds.med")} {TR(lang, "cruds.edit")}</h4>
        </div>
        <div className="card-body">
            {
                drugLoading ? <Loading /> :
                    <div className="basic-form">
                        <form onSubmit={(e) => saveEditingDrug(e)}>
                            <div className="row">
                                <div className="form-group mb-3 col-md-6">
                                    <label>{TR(lang, "table.name")}</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => dispatch(drugEditDataAction({ ...data, name: e.target.value }))}
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
                                            onClick={() => dispatch(drugEditDataAction({ ...data, is_rx: !data.is_rx, is_otc: data.is_rx }))}
                                            checked={data.is_rx}
                                        />
                                        <label className="form-check-label mx-2 my-0" for="flexRadioDefault1"> Rx </label>
                                    </div>
                                    <div className="form-check col-xl-2 col-md-6 col-sm-6  d-flex align-items-center mx-2">
                                        <input
                                            className="form-check-input"
                                            type="radio"
                                            id="flexRadioDefault2"
                                            onClick={() => dispatch(drugEditDataAction({ ...data, is_otc: !data.is_otc, is_rx: data.is_otc }))}
                                            checked={data.is_otc}
                                        />
                                        <label className="form-check-label mx-2 my-0" for="flexRadioDefault2">  OTC </label>
                                    </div>
                                </div>
                                {/* <div className="form-group mb-3 col-md-6">
                                <div className="input-group" style={{marginTop: '28.5px'}}>
                                    <input
                                        value={NumberToStr(data.ref_price)}
                                        onChange={(e) => dispatch(drugEditDataAction({...data, ref_price: StrtoNumber(e.target.value)}))}
                                        className="form-control" 
                                        aria-label="Text input with dropdown button" 
                                        />
                                    <select
                                        defaultValue={"option"}
                                        className="form-select"
                                        value={data.ref_price_ccy}
                                        onChange={(e) => dispatch(drugEditDataAction({...data, ref_price_ccy: e.target.value}))}
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
                                        onChange={e => dispatch(drugEditDataAction({ ...data, di_id: e.value }))}
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
                                        onChange={e => dispatch(drugEditDataAction({ ...data, df_id: e.value }))}
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
                                        onChange={e => dispatch(drugEditDataAction({ ...data, dfg_id: e.value }))}
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
                                        onChange={e => dispatch(drugEditDataAction({ ...data, dtg_id: e.value }))}
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
                                        API={DType}
                                        arr_key='dType'
                                        options={list.dType}
                                        onChange={e => dispatch(drugEditDataAction({ ...data, dt_id: e.value }))}
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
                                        onChange={e => dispatch(drugEditDataAction({ ...data, trademark_id: e.value }))}
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
                                    <div className='row align-items-center'>
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
                                        <div className='col-md-3 mb-1'>
                                            {(count.length !== 1) && <button className={`btn btn-sm btn-primary ${document.body.clientWidth > 576 ? 'mr-3' : 'mr-1'}`} onClick={(e) => { handleDelete(e, index) }}><i className='fa fa-minus'></i></button>}
                                            {(index + 1 === count.length) && <button className="btn btn-sm btn-success" onClick={(e) => { handleAdd(e) }}><i className='fa fa-plus'></i></button>}
                                        </div>
                                    </div>
                                )
                            })
                            }
                            <div className='d-flex my-2 float-end'>
                                <Link className='btn btn-warning w-100 text-white text-decoration-none' to='/admin/drugs'>{TR(lang, "content.cancel")}</Link>
                                <button type='submit' className="ms-3 btn btn-primary">{TR(lang, "content.save")}</button>
                            </div>
                        </form>
                    </div>
            }

        </div>
    </div>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        data: state.drugs.editData,
        list: state.drugs.editList,
        listLoading: state.drugs.editListLoading,
    };
};


export default connect(mapStateToProps)(EditDrugs);
