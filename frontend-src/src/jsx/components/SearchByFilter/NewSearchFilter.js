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

const NewSearchFilter = (props) => {
    const {
        toggle,
        setToggle,
        date,
        api_list,
        handleSearch,
        defaultList,
        selectedCheckbox,
        dataIDList,
        dataIdOptions,
        lastUpdateDate,
        productTypes,
        dataType,
        lang,
    } = props;
    const [ids, setIds] = useState({ ...defaultList });
    const [list, setList] = useState({ ...defaultList, dtID: [...productTypes] });
    const country_id = 19;
    const [tempDataType, setTempDataType] = useState(dataType);
    const [tempSelectedCheckbox, setTempSelectedCheckbox] = useState(selectedCheckbox);
    const [selectedList, setSelectedList] = useState({ ...defaultList });
    const [loading, setLoading] = useState({
        drugID: false,
        distID: false,
        companyID: false,
        mfID: false,
        dfID: false,
        dfgID: false,
        innID: false,
        dtgID: false,
        trademarkID: false,
        countryID: false,
        region: false,
        district: false,
    });
    const [datePicker, setDatePicker] = useState(date);
    const [timer, setTimer] = useState(null);
    const handleChange = (value, index, id) => {
        const DATA = [...datePicker];
        DATA[index][id] = formatDateToDay(value);
        setDatePicker(DATA);
    }
    const handleChangeSelect = (e, key) => {
        setSelectedList(data => {
            data[key] = e || [];
            return { ...data }
        })
        const tempIds = e?.map(key => key.value) || [];
        setIds(data => {
            data[key] = [...tempIds];
            return { ...data }
        })
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

    const handleChangeProductTypesSelect = (e) => {
        setSelectedList(data => {
            data['drugID'] = [];
            data['dtID'] = e || [];
            return { ...data }
        })
        const tempIds = e?.map(key => key.value) || [];
        setIds(data => {
            data['drugID'] = [];
            data['dtID'] = [...tempIds];
            return { ...data }
        });
        setList(data => {
            data['drugID'] = [];
            return { ...data }
        })
    }

    const filterDb = (arr_key, API, value, index, additional) => {
        const new_list = [];
        setLoading(data => {
            data[arr_key] = true;
            return { ...data }
        });
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            if (API.name === "drug") {
                API.search(value, ids).then((res) => {
                    res.data.data.forEach(key => { if (!ids[arr_key].includes(key.id)) new_list.push({ value: key.id, label: key.name || "" }) })
                    setList(data => {
                        data[arr_key] = [...selectedList[arr_key], ...new_list]
                        return { ...data }
                    });
                    setLoading(data => { data[arr_key] = false; return { ...data } });
                })
            } else {
                API.select(true, false, value, ids).then((res) => {
                    res.data.data.forEach(key => { if (!ids[arr_key].includes(key.id)) new_list.push({ value: key.id, label: key.full_name || "" }) })
                    setList(data => {
                        data[arr_key] = [...selectedList[arr_key], ...new_list]
                        return { ...data }
                    });
                    setLoading(data => { data[arr_key] = false; return { ...data } });
                })
                // }
            }
        }, 1000)
        setTimer(newTimer);
    };

    useEffect(() => {
        setTempDataType(dataType)
        if (toggle) {
            setLoading(data => { data.region = true; return { ...data } })
            Region.getList(1, 100, 1, 0, [], { key: "id", "value": true }).then((res) => {
                setList({
                    ...dataIdOptions, regionID: res.data.data.map(key => ({
                        value: key.id,
                        label: key.name
                    }))
                });
                setLoading(data => { data.region = false; return { ...data } })
            })
            setIds({ ...dataIDList });
            setDatePicker(date);
            setSelectedList({ ...dataIdOptions });
            setTempSelectedCheckbox(selectedCheckbox)
        } else {
            setTempSelectedCheckbox(selectedCheckbox)
            setSelectedList({ ...defaultList });
            setIds({ ...defaultList });
            setList({ ...defaultList, dtID: [...productTypes] });
            setDatePicker([getThisYear(lastUpdateDate)]);
        }
    }, [toggle])

    return (
        <>
            <div className={`sidebar-right media-width ${toggle ? "show" : ""}`}>
                <div className="bg-overlay" onClick={() => setToggle(!toggle)}></div>
                <div className="sidebar-right-inner media-width p-4" style={{ overflowY: "auto", maxHeight: "600px" }}>
                    <div className="mt-3">
                        {
                            tempDataType === 2 ?
                                <div className="row">
                                    <div className="col-md-6">
                                        <h6 className="m-0">{TR(lang, "products.region")}</h6>
                                        <div className="row">
                                            <input
                                                className="col-md-1"
                                                type="checkbox"
                                                checked={tempSelectedCheckbox === "region"}
                                                onChange={() => {
                                                    setTempSelectedCheckbox("region");
                                                }}
                                            />
                                            <ServerSelect
                                                className="col-md-11"
                                                id="region"
                                                API={Region}
                                                arr_key='regionID'
                                                options={list["regionID"]}
                                                onChange={e => handleChangeSelect(e, "regionID")}
                                                value={list["regionID"].filter(key => ids["regionID"].includes(key.value))}
                                                isLoading={loading["region"]}
                                                filterDb={filterDb}
                                                additional={{ country_id }}
                                                placeholder={TR(lang, "products.region")}
                                                isMulti
                                            />
                                        </div>
                                    </div>
                                    {
                                        ids["regionID"].length == 1 ?
                                            <div className="col-md-6">
                                                <h6 className="m-0">{TR(lang, "products.district")}</h6>
                                                <div className="row">
                                                    <input
                                                        className="col-md-1"
                                                        type="checkbox"
                                                        checked={tempSelectedCheckbox === "district"}
                                                        onChange={() => {
                                                            setTempSelectedCheckbox("district");
                                                        }}
                                                    />
                                                    <ServerSelect
                                                        className="col-md-11"
                                                        id="district"
                                                        API={District}
                                                        arr_key='districtID'
                                                        options={list["districtID"]}
                                                        onChange={e => handleChangeSelect(e, "districtID")}
                                                        value={list["districtID"].filter(key => ids["districtID"].includes(key.value))}
                                                        isLoading={loading["district"]}
                                                        filterDb={filterDb}
                                                        additional={{ region_id: list["district"] ? list["districtID"][0] : null }}
                                                        placeholder={TR(lang, "products.district")}
                                                        isMulti
                                                    />
                                                </div>
                                            </div> : null
                                    }
                                </div>
                                : null
                        }
                    </div>
                    <div className="row mb-2">
                        <h6> {TR(lang, "products.dt")}</h6>
                        <MultiSelect
                            placeholder="products.dt"
                            onChange={e => handleChangeProductTypesSelect(e)}
                            value={list.dtID.filter(key => ids.dtID.includes(key.value))}
                            options={list.dtID}
                        />
                    </div>
                    <div className="row">
                        {
                            api_list.map(element => {
                                if (tempDataType === 2 && element.key === "companyID") {
                                    return null;
                                }
                                const is_active = tempSelectedCheckbox === element.checkbox_key;
                                return (
                                    <div key={element.key} className="col-md-6">
                                        <h6 className="m-0">{element.title}</h6>
                                        <div className="row">
                                            <input
                                                className="col-md-1"
                                                type="checkbox"
                                                checked={is_active}
                                                onChange={() => {
                                                    setTempSelectedCheckbox(element.checkbox_key);
                                                }}
                                            />
                                            <ServerSelect
                                                className="col-md-11"
                                                API={element.api}
                                                arr_key={element.key}
                                                options={list[element.key]}
                                                onChange={e => handleChangeSelect(e, element.key)}
                                                isMulti
                                                value={list[element.key].filter(key => ids[element.key].includes(key.value))}
                                                isLoading={loading[element.key]}
                                                filterDb={filterDb}
                                                required
                                            />
                                        </div>
                                    </div>
                                )
                            })
                        }
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
                                            showMonthDropdown
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
                        <button className='btn btn-primary media-w-btn' onClick={() => handleSearch(datePicker, tempSelectedCheckbox, ids, list, tempDataType)}>{TR(lang, "content.search")}</button>
                    </div>
                </div>
            </div>
        </>
    );
};
export default NewSearchFilter;