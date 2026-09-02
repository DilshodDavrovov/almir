import React, { useEffect, useState } from "react";
import { formatDateToDay, getThisYear, stringToDate } from "../../../utils";
import { TR } from "../../../utils/helpers";
import ServerSelect from '../React-Select-Server';
import ReactDatePicker from "react-datepicker";
import 'react-datepicker/dist/react-datepicker.css'
import MaskedInput from 'react-text-mask'
import { autoCorrectedDatePipe } from '../../../utils/index';
import MultiSelect from "../MultiSelect";
import Region from '../../../services/cruds/RegionService';
import District from '../../../services/cruds/DistrictService';
const AnalyzeFilter = (props) => {
    const {
        toggle,
        setToggle,
        productTypes,
        date,
        handleSearch,
        dataIDList,
        dataIdOptions,
        selectedProductTypeIds,
        lang,
        lastUpdateDate,
        API,
        dataType,
        others,
    } = props;
    const [ids, setIds] = useState(dataIDList);
    const country_id = 19;
    const [regionId, setRegionId] = useState(null);
    const [districtId, setDistrictId] = useState(null);
    const [tempDataType, setTempDataType] = useState(dataType);
    const [productTypeIds, setProductTypeIds] = useState(selectedProductTypeIds);
    const [list, setList] = useState(dataIdOptions);
    const [selectedList, setSelectedList] = useState(dataIdOptions);
    const [selectedProductTypes, setSelectedProductTypes] = useState(productTypes.filter(e => selectedProductTypeIds.includes(e.value)));
    const [listDistrict, setListDistrict] = useState([]);
    const [listRegion, setListRegion] = useState([]);
    const [loadingDistrict, setLoadingDistrict] = useState(false);
    const [loadingRegion, setLoadingRegion] = useState(false);
    const [loading, setLoading] = useState(false);
    const [datePicker, setDatePicker] = useState(date);
    const [timer, setTimer] = useState(null);
    const handleChange = (value, index, id) => {
        const DATA = [...datePicker];
        DATA[index][id] = formatDateToDay(value);
        setDatePicker(DATA);
    }
    const handleChangeSelect = (e) => {
        setSelectedList(e || [])
        const tempIds = e?.map(key => key.value) || [];
        setIds([...tempIds])
    }
    const handleChangeProductTypesSelect = (e) => {
        const temp = e || [];
        setSelectedProductTypes(temp)
        const tempIds = temp.map(key => key.value);
        setProductTypeIds([...tempIds]);

        if (API.name === "drug") {
            setIds([]);
            setSelectedList([]);
            setList([]);
        }

    }
    const handleAdd = () => {
        setDatePicker([...datePicker, { fromDate: null, toDate: null }])
    }

    const handleDelete = (index) => {
        const DATA = [];
        datePicker.forEach((key, i) => {
            if (i !== index) DATA.push(key);
        })
        setDatePicker(DATA);
    }

    const handleClear = (index) => {
        const DATA = [...datePicker];
        DATA[index] = { fromDate: null, toDate: null };
        setDatePicker([...DATA]);
    }

    const filterDb = (arr_key, API, value, index, additional) => {
        const new_list = [];
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            if (API.name === "drug") {
                setLoading(true);
                API.search(value, productTypeIds).then((res) => {
                    res.data.data.forEach(key => { if (!ids.includes(key.id)) new_list.push({ value: key.id, label: key.name || "" }) })
                    setList([...selectedList, ...new_list]);
                    setLoading(false);
                })
            } else {

                if (arr_key === 'region') {
                    setLoadingRegion(true);
                    API.select(true, false, value, additional).then((res) => {
                        res.data.data.forEach(key => { if (regionId !== key.id) new_list.push({ value: key.id, label: key.full_name || "" }) })
                        setListRegion(prev => {
                            return [...prev, ...new_list]
                        })

                        setLoadingRegion(false)
                    })
                } else if (arr_key === 'district') {
                    setLoadingDistrict(true)
                    API.select(true, false, value, additional).then((res) => {
                        res.data.data.forEach(key => { if (districtId !== key.id) new_list.push({ value: key.id, label: key.full_name || "" }) })
                        setListDistrict(prev => {
                            return [...prev, ...new_list]
                        })
                        setLoadingDistrict(false)
                    })
                } else {
                    setLoading(true)
                    API.select(true, false, value, additional).then((res) => {
                        res.data.data.forEach(key => { if (!ids.includes(key.id)) new_list.push({ value: key.id, label: key.full_name || "" }) })
                        setList([...selectedList, ...new_list])
                        setLoading(false)
                    })
                }

            }
        }, 1000)
        setTimer(newTimer);
    };
    useEffect(() => {
        setTempDataType(dataType)
        if (toggle) {
            setIds(dataIDList);
            setRegionId(others?.region_id || null);
            setDistrictId(others?.district_id || null);
            setListRegion(others?.listRegion || []);
            setListDistrict(others?.listDistrict || [])
            setList(dataIdOptions);
            setDatePicker(date);
            setSelectedList(dataIdOptions);
        } else {
            setRegionId(null);
            setDistrictId(null);
            setListRegion([]);
            setListDistrict([])
            setSelectedList([]);
            setIds([]);
            setList([]);
            setDatePicker([getThisYear(lastUpdateDate)]);
        }
    }, [toggle])
    return (
        <>
            <div className={`sidebar-right media-width ${toggle ? "show" : ""}`}>
                <div className="bg-overlay" onClick={() => setToggle(!toggle)}></div>
                <div className="sidebar-right-inner media-width p-4">
                    <div className="mt-3">
                        {
                            tempDataType === 2 ?
                                <>
                                    <div className="form-group mb-3 col-md-6">
                                        <label className='black-font' htmlFor='region'>{TR(lang, "products.region")}</label>
                                        <ServerSelect
                                            id="region"
                                            API={Region}
                                            arr_key='region'
                                            options={listRegion}
                                            onChange={e => {
                                                if (e.value !== regionId) {
                                                    setRegionId(e.value)
                                                    setDistrictId(null)
                                                    setListDistrict([])
                                                }
                                            }}
                                            value={listRegion.filter(key => key.value === regionId)}
                                            isLoading={loadingRegion}
                                            filterDb={filterDb}
                                            additional={{ country_id }}
                                            placeholder={TR(lang, "products.region")}
                                        />
                                    </div>
                                    {
                                        regionId ?
                                            <div className="form-group mb-3 col-md-6">
                                                <label className='black-font' htmlFor='district'>{TR(lang, "products.district")}</label>
                                                <ServerSelect
                                                    id="district"
                                                    API={District}
                                                    arr_key='district'
                                                    options={listDistrict}
                                                    onChange={e => setDistrictId(e.value)}
                                                    value={listDistrict.filter(key => key.value === districtId)}
                                                    isLoading={loadingDistrict}
                                                    filterDb={filterDb}
                                                    additional={{ regionID: regionId }}
                                                    placeholder={TR(lang, "products.district")}
                                                />
                                            </div>
                                            : null
                                    }
                                </>
                                : null
                        }
                    </div>

                    <div className={"mt-3"}>
                        <h6> {TR(lang, "products.dt")}</h6>
                        <MultiSelect
                            placeholder="products.dt"
                            onChange={e => handleChangeProductTypesSelect(e)}
                            value={selectedProductTypes}
                            options={productTypes}
                        />
                    </div>
                    <div className={"mt-3"}>
                        <h6> {TR(lang, "content.analyzeTitle")}</h6>
                        <ServerSelect
                            API={API}
                            options={list}
                            onChange={e => handleChangeSelect(e)}
                            isMulti
                            value={list.filter(key => ids.includes(key.value))}
                            isLoading={loading}
                            filterDb={filterDb}
                            required
                        />
                    </div>

                    <div className='my-2'>
                        {
                            datePicker.map((key, index) =>
                                <div key={index} className='my-3'>
                                    <h6 className='text-nowrap me-2'> {index + 1} -  {TR(lang, "content.period")}:</h6>
                                    <div className="d-flex">

                                        <ReactDatePicker
                                            showYearDropdown
                                            showMonthDropdown
                                            dropdownMode="select"
                                            className="form-control form-control-sm"
                                            onSelect={e => handleChange(e, index, 'fromDate')}
                                            onChange={e => handleChange(e, index, 'fromDate')}
                                            maxDate={key.toDate ? stringToDate(key.toDate, 'dd-mm-yyyy', '-') : null}
                                            selected={key.fromDate ? stringToDate(key.fromDate, 'dd-mm-yyyy', '-') : null}
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
                                        <ReactDatePicker
                                            showYearDropdown
                                            dropdownMode="select"
                                            className="form-control form-control-sm"
                                            onSelect={e => handleChange(e, index, 'toDate')}
                                            onChange={e => handleChange(e, index, 'toDate')}
                                            minDate={key.fromDate ? stringToDate(key.fromDate, 'dd-mm-yyyy', '-') : null}
                                            selected={key.toDate ? stringToDate(key.toDate, 'dd-mm-yyyy', '-') : null}
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
                                        <div className="d-flex align-items-start flex-column juctify-content-between filter-x">
                                            <i onClick={() => handleClear(index)} className="fas fa-broom align-bottom d-flex align-items-center" role='button'></i>
                                            {
                                                (datePicker.length > 1) ?
                                                    <i onClick={() => handleDelete(index)} className="align-bottom fas fa-solid fa-xmark mt-2" role='button'></i> : ""

                                            }
                                            {
                                                (index < 3 && datePicker.length - 1 === index) ?
                                                    <i className="cursor-pointer fas fa-plus add-plus" onClick={() => handleAdd()}></i> : ""
                                            }
                                        </div>

                                    </div>
                                </div>
                            )
                        }
                    </div>
                    <div className='d-flex justify-content-between'>
                        <button className='btn btn-danger media-w-btn' onClick={() => setToggle(!toggle)}> {TR(lang, "content.close")}</button>
                        <button className='btn btn-primary media-w-btn' onClick={() => handleSearch(datePicker, ids, productTypeIds, list, tempDataType, tempDataType === 2 ? { region_id: regionId, district_id: districtId, listRegion, listDistrict } : {})}>{TR(lang, "content.search")}</button>
                    </div>
                </div>
            </div>
        </>
    );
};

export default AnalyzeFilter;