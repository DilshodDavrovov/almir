import React, { useEffect, useState } from 'react';
import AnalyzeDataTable from './AnalyzeDataTable';
import { buildParams, differencePercent, diffSelector, GetDiffferens, getThisYear, isDiffPeriod, NumberToStr, parseParams } from '../../../utils'
import { TR } from '../../../utils/helpers';
import { connect } from 'react-redux';
import ActivityApi from '../../../services/ActivityService'
import AnalyzeApi from '../../../services/AnalyzeService'
import Region from '../../../services/cruds/RegionService';
import District from '../../../services/cruds/DistrictService';
import { useLocation, useHistory } from 'react-router-dom';
const Analyze = (props) => {
    const location = useLocation();
    const history = useHistory();
    const { lang, lastUpdateDate, title, API } = props;
    const getThisKeys = (str) => {
        if (str === '/analyze/drugs') {
            return ['totalDrugNames', 'totalDrugNamesQty', null, 'drugs']
        } else if (str === '/analyze/companies') {
            return ['isOtc', 'totalCompanies', 'totalCompaniesQty', 'sc']
        } else if (str === '/analyze/trademark') {
            return ['isOtc', 'totalTrademarks', 'totalTrademarksQty', 'trademark']
        } else if (str === '/analyze/manufacturers') {
            return ['isOtc', 'totalManufacturers', 'totalManufacturersQty', 'mf']
        } else if (str === '/analyze/d-form') {
            return ['isOtc', 'totalDrugForms', 'totalDrugFormsQty', 'df']
        } else if (str === '/analyze/d-farm-groups') {
            return ['isOtc', 'totalDrugFormGroups', 'totalDrugFormGroupsQty', 'dfg']
        } else if (str === '/analyze/t-groups') {
            return ['isOtc', 'totalDrugTempGroups', 'totalDrugTempGroupsQty', 'dtg']
        } else if (str === '/analyze/distributors') {
            return ['isOtc', 'totalDistributors', 'totalDistributorsQty', 'dist']
        } else if (str === '/analyze/inn') {
            return ['isOtc', 'totalDrugInn', 'totalDrugInnQty', 'inn']
        }
    }
    const columnTitles = {
        isOtc: "table.rx_otc",
        totalCommonPerPrice: "table.turnover",
        totalDrugNames: "table.topDrugs",
        totalDrugNamesQty: "table.qtyDrug",
        totalCompanies: "table.topComp",
        totalCompaniesQty: "table.qtyComp",
        totalTrademarks: "table.topTd",
        totalTrademarksQty: "table.qtyTd",
        totalManufacturers: "table.topMf",
        totalManufacturersQty: "table.qtyMf",
        totalDrugForms: "table.topDf",
        totalDrugFormsQty: "table.qtyDf",
        totalDrugFormGroups: "table.topFG",
        totalDrugFormGroupsQty: "table.qtyFG",
        totalDrugTempGroups: "table.topTG",
        totalDrugTempGroupsQty: "table.qtyTG",
        totalDistributors: "table.topDist",
        totalDistributorsQty: "table.qtyDist",
        totalDrugInn: "table.topMnn",
        totalDrugInnQty: "table.qtyMnn"
    }
    const columnActions = _.omit({
        isOtc: true,
        totalCommonPerPrice: true,
        totalDrugNamesQty: false,
        totalDrugNames: false,
        totalCompaniesQty: false,
        totalCompanies: false,
        totalTrademarksQty: false,
        totalTrademarks: false,
        totalManufacturersQty: false,
        totalManufacturers: false,
        totalDrugFormsQty: false,
        totalDrugForms: false,
        totalDrugFormGroupsQty: false,
        totalDrugFormGroups: false,
        totalDrugTempGroupsQty: false,
        totalDrugTempGroups: false,
        totalDistributorsQty: false,
        totalDistributors: false,
        totalDrugInnQty: false,
        totalDrugInn: false,
    }, getThisKeys(location.pathname));

    const defaultColumns = _.omit({
        totalCommonPerPrice: {
            accessor: `totalCommonPerPrice`,
            total_accessor: `total`,
            price: [],
            price_uz: [],
            percent_price: [],
            percent_qty: [],
            qty: [],
            isPrice: true,
            isActivePrice: columnActions.totalCommonPerPrice,
            default: { "qty": "0", "USD": "0", "UZS": "0", "EUR": "0", "RUB": "0" },
        },
        totalDrugNames: {
            qty: [],
            tops: [],
            accessor: `totalDrugNames`,
            isActiveTops: columnActions.totalDrugNames,
            isActiveQty: columnActions.totalDrugNamesQty,
            default: []
        },
        totalCompanies: {
            qty: [],
            tops: [],
            accessor: `totalCompanies`,
            isActiveTops: columnActions.totalCompanies,
            isActiveQty: columnActions.totalCompaniesQty,
            default: []
        },
        totalTrademarks: {
            qty: [],
            tops: [],
            accessor: `totalTrademarks`,
            isActiveTops: columnActions.totalTrademarks,
            isActiveQty: columnActions.totalTrademarksQty,
            default: []
        },
        totalManufacturers: {
            qty: [],
            tops: [],
            accessor: `totalManufacturers`,
            isActiveTops: columnActions.totalManufacturers,
            isActiveQty: columnActions.totalManufacturersQty,
            default: []
        },
        totalDrugForms: {
            qty: [],
            tops: [],
            accessor: `totalDrugForms`,
            isActiveTops: columnActions.totalDrugForms,
            isActiveQty: columnActions.totalDrugFormsQty,
            default: []
        },
        totalDrugFormGroups: {
            qty: [],
            tops: [],
            accessor: `totalDrugFormGroups`,
            isActiveTops: columnActions.totalDrugFormGroups,
            isActiveQty: columnActions.totalDrugFormGroupsQty,
            default: []
        },
        totalDrugTempGroups: {
            qty: [],
            tops: [],
            accessor: `totalDrugTempGroups`,
            isActiveTops: columnActions.totalDrugTempGroups,
            isActiveQty: columnActions.totalDrugTempGroupsQty,
            default: []
        },
        totalDistributors: {
            qty: [],
            tops: [],
            accessor: `totalDistributors`,
            isActiveTops: columnActions.totalDistributors,
            isActiveQty: columnActions.totalDistributorsQty,
            default: []
        },
        totalDrugInn: {
            qty: [],
            tops: [],
            accessor: `totalDrugInn`,
            isActiveTops: columnActions.totalDrugInn,
            isActiveQty: columnActions.totalDrugInnQty,
            default: []
        },
    }, getThisKeys(location.pathname))
    const [columnData, setColumnData] = useState(defaultColumns);
    const [columnFilter, setColumnFilter] = useState(columnActions);
    const [toggle, setToggle] = useState(false);
    const [sort, setSort] = useState({ key: 'USD', value: true, period: 1 });
    const [date, setDate] = useState([getThisYear(lastUpdateDate)]);
    const [dataIDList, setDataIDList] = useState([]);
    const [selectedProductTypeIds, setSelectedProductTypeIds] = useState([]);
    const [dataIdOptions, setDataIdOptions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [data, setData] = useState([]);
    const [total, setTotal] = useState({});
    const [others, setOthers] = useState({ district_id: null, region_id: null, listRegion: [], listDistrict: [] });
    const [additional, setAdditional] = useState({});
    const [dataType, setDataType] = useState(1);
    const [totalExcel, setTotalExcel] = useState({
        price: {
            price: [],
            price_uz: [],
            qty: []
        },
        differencePrice: {
            price: [],
            price_uz: [],
            qty: []
        }
    })
    const [price, setPrice] = useState("USD");
    const [columns, setColumns] = useState([]);
    const priceOptions = [{ value: "USD", label: "$" }, { value: "EUR", label: "€" }, { value: "RUB", label: "₽" }];
    const getPeriod = (date, i, lang) => {
        return date.length > 1 ? `(${date[i].fromDate.replaceAll('-', '/')} - ${date[i].toDate.replaceAll('-', '/')})` : "";
        // return date.length > 1 ? `${i + 1}-${TR(lang, "content.periodShort")}.` : "";
    }
    const setDefault = (
        limit = 25,
        sortBy = 'USD',
        sortByDesc = true,
        sortPeriod = 1,
        date = [getThisYear(lastUpdateDate)],
        dataIDList = [],
        selectedProductTypeIds = [],
        columnFilter = { ...columnActions },
        dataType = 1,
        others = { district_id: null, region_id: null, listRegion: [], listDistrict: [] }
    ) => {
        const temp_additional = {}
        setPage(1);
        setDataType(dataType)
        setDate(date);
        setLimit(limit);
        const tempColumnFilter = (dataType === 2) ? { ...columnFilter, totalCompanies: false, totalCompaniesQty: false } : columnFilter
        setColumnFilter(tempColumnFilter)
        setDataIDList(dataIDList);
        setSelectedProductTypeIds(selectedProductTypeIds);
        if (others) {
            if (others.region_id) {
                temp_additional.region_id = others.region_id;
                Region.getById(others.region_id).then(res => {
                    setOthers(prev => {
                        return { ...prev, region_id: others.region_id, listRegion: [{ value: res.data.data.id, label: res.data.data.name || "" }] }
                    })
                })
            }
            if (others.district_id) {
                temp_additional.district_id = others.district_id;
                District.getById(others.district_id).then(res => {
                    setOthers(prev => {
                        return { ...prev, district_id: others.district_id, listDistrict: [{ value: res.data.data.id, label: res.data.data.name || "" }] }
                    })
                })
            }
            setAdditional(temp_additional)
        }
        
        if (dataIDList.length) {
            if (API.name === "drug") {
                API.getIdsList(dataIDList.toString()).then((res) => {
                    setDataIdOptions([...res.data.data.map(key => ({ value: key.id, label: key.full_name || "" }))])
                })
            } else {
                API.getIdsList(dataIDList.toString()).then((res) => {
                    setDataIdOptions([...res.data.data.map(key => ({ value: key.id, label: key.full_name || "" }))])
                })
            }
        }
        setSort({ key: sortBy, value: !!sortByDesc, period: sortPeriod });
        const columnData = createColumns(price, date, tempColumnFilter);
        getTotalList(date, dataIDList, price, selectedProductTypeIds, dataType, temp_additional).then((total) => {
            getAllList(total, date, dataIDList, selectedProductTypeIds, tempColumnFilter, columnData, limit, 1, { key: sortBy, value: !!sortByDesc, period: sortPeriod }, price, dataType, temp_additional);
        }).catch(() => { });
    }
    const handleReboot = () => {
        setLoading(true);
        setDefault();
    }
    const handleChangePrice = (value, date) => {
        setLoading(true);
        setPrice(value);
        const columnData = createColumns(value, date, columnFilter);
        getTotalList(date, dataIDList, value, selectedProductTypeIds, dataType, additional).then((total) => {
            getAllList(total, date, dataIDList, selectedProductTypeIds, columnFilter, columnData, limit, 1, sort, value, dataType, additional)
        }).catch(() => { });
    }
    const completeCols = (columnFilter) => {
        const temp = JSON.parse(JSON.stringify(defaultColumns));
        for (const key in defaultColumns) {
            if (key !== 'totalCommonPerPrice') {
                temp[key].isActiveTops = columnFilter[key];
                temp[key].isActiveQty = columnFilter[`${key}Qty`];
            } else {
                temp[key].isActivePrice = columnFilter[key];
            }
        }
        return { ...temp };
    }
    const createColumns = (price, date, columnFilter) => {
        const tempColumnData = JSON.parse(JSON.stringify(completeCols(columnFilter)));
        
        for (const key in tempColumnData) {
            const temp = { ...tempColumnData[key] };
            date.forEach((_, i) => {
                const period = i + 1;
                if (temp.isPrice) {
                    if (temp.isActivePrice) {
                        tempColumnData[key].price.push({
                            Header: () => <div className='text-center d-inline'>
                                {`${TR(lang, "table.turnover")}`} {`${TR(lang, "table.turnoverUZB")}`} {getPeriod(date, i, lang)}
                                <select
                                    style={{ width: "80px" }}
                                    className="form-select ms-2"
                                    value={price}
                                    onChange={(e) => handleChangePrice(e.target.value, date)}
                                >
                                    {priceOptions.map(ccy => {
                                        return <option key={ccy.value} value={ccy.value}>{ccy.label}</option>
                                    })}
                                </select>
                            </div>,
                            HeaderTitle: `${TR(lang, "table.turnover")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}.${price}`,
                            role: 'price',
                            total_accessor: `period_${i + 1}.${price}`,
                            excel_total_accessor: `price.price[${i}]`,
                            serverSort: 'USD',
                            period: i + 1,
                            Cell: ({ value }) => {
                                return <div>{value ? NumberToStr(value) : 0}</div>;
                            }
                        });
                        tempColumnData[key].price_uz.push({
                            Header: () => <div className='text-center d-inline'>
                                {`${TR(lang, "table.turnover")} ${TR(lang, "table.turnoverUZB")} ${TR(lang, "table.inSum")}`} {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.turnover")} ${TR(lang, "table.turnoverUZB")} ${TR(lang, "table.inSum")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}.UZS`,
                            role: 'price_uz',
                            total_accessor: `period_${i + 1}.UZS`,
                            excel_total_accessor: `price.price_uz[${i}]`,
                            Cell: ({ value }) => {
                                return <div>{value ? NumberToStr(value) : 0}</div>;
                            }
                        });
                        tempColumnData[key].qty.push({
                            Header: () => <div className='text-center d-inline'>
                                {TR(lang, "table.turnOverCompPac")} {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.turnOverCompPac")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}.qty`,
                            role: 'count',
                            total_accessor: `period_${i + 1}.qty`,
                            excel_total_accessor: `price.qty[${i}]`,
                            serverSort: 'qty',
                            period: i + 1,
                            Cell: ({ value }) => {
                                return <div>{value ? NumberToStr(value) : 0}</div>;
                            }
                        });
                        tempColumnData[key].percent_price.push({
                            Header: () => <div className='text-center d-inline'>
                                {TR(lang, "table.perc_price")} {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.perc_price")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}.percent_price`,
                            role: 'percent',
                            minWidth: 90,
                            Cell: ({ value }) => {
                                return <div>{value ? NumberToStr(value) : 0} %</div>;
                            }
                        });
                        tempColumnData[key].percent_qty.push({
                            Header: () => <div className='text-center d-inline'>
                                {TR(lang, "table.perc_qty")} {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.perc_qty")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}.percent_qty`,
                            role: 'percent',
                            minWidth: 90,
                            Cell: ({ value }) => {
                                return <div>{value ? NumberToStr(value) : 0} %</div>;
                            }
                        });
                        if (isDiffPeriod(date.length, period) && false) {
                            tempColumnData[key].price.push({
                                Header: () => <div className='text-center d-inline'>{i + 1}{TR(lang, "content.periodShort")} - {i}{TR(lang, "content.periodShort")}</div>,
                                HeaderTitle: `(${i + 1} - ${i})`,
                                accessor: `differencePrice.price[${diffSelector(i)}]`,
                                role: 'diffPrice.price',
                                total_accessor: `differencePrice.price[${diffSelector(i)}]`,
                                excel_total_accessor: `differencePrice.price[${diffSelector(i)}]`,
                                Cell: ({ value }) => {
                                    return GetDiffferens(value, 2);
                                }
                            });
                            tempColumnData[key].price_uz.push({
                                Header: () => <div className='text-center d-inline'>{i + 1}{TR(lang, "content.periodShort")} - {i}{TR(lang, "content.periodShort")}</div>,
                                HeaderTitle: `(${i + 1} - ${i})`,
                                accessor: `differencePrice.price_uz[${diffSelector(i)}]`,
                                role: 'diffPrice.price_uz',
                                total_accessor: `differencePrice.price_uz[${diffSelector(i)}]`,
                                excel_total_accessor: `differencePrice.price_uz[${diffSelector(i)}]`,
                                Cell: ({ value }) => {
                                    return GetDiffferens(value, 2);
                                }
                            });
                            tempColumnData[key].qty.push({
                                Header: () => <div className='text-center d-inline'>{i + 1}{TR(lang, "content.periodShort")} - {i}{TR(lang, "content.periodShort")}</div>,
                                HeaderTitle: `(${i + 1} - ${i})`,
                                accessor: `differencePrice.qty[${diffSelector(i)}]`,
                                role: 'diffPrice.qty',
                                total_accessor: `differencePrice.qty[${diffSelector(i)}]`,
                                excel_total_accessor: `differencePrice.qty[${diffSelector(i)}]`,
                                Cell: ({ value }) => {
                                    return GetDiffferens(value, 2);
                                }
                            });
                            tempColumnData[key].percent_price.push({
                                Header: () => <div className='text-center d-inline'>{i + 1}{TR(lang, "content.periodShort")} - {i}{TR(lang, "content.periodShort")}</div>,
                                HeaderTitle: `(${i + 1} - ${i})`,
                                accessor: `differencePrice.percent_price[${diffSelector(i)}]`,
                                role: 'dif_percent',
                                Cell: ({ value }) => {
                                    return GetDiffferens(value, 2, '%');
                                }
                            });
                            tempColumnData[key].percent_qty.push({
                                Header: () => <div className='text-center d-inline'>{i + 1}{TR(lang, "content.periodShort")} - {i}{TR(lang, "content.periodShort")}</div>,
                                HeaderTitle: `(${i + 1} - ${i})`,
                                accessor: `differencePrice.percent_qty[${diffSelector(i)}]`,
                                role: 'dif_percent',
                                Cell: ({ value }) => {
                                    return GetDiffferens(value, 2, '%');
                                }
                            });
                        }
                    }
                } else {
                    if (temp.isActiveTops) {
                        tempColumnData[key].tops.push({
                            Header: () => <div className='text-center d-inline'>{TR(lang, columnTitles[temp.accessor])} {getPeriod(date, i, lang)}</div>,
                            HeaderTitle: `${TR(lang, columnTitles[temp.accessor])} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}`,
                            Cell: ({ value }) => {
                                if (value && value.length) {
                                    return value.map((key, index) => {
                                        return <div key={index} className='cut-text m-0'>{key['name']} = {NumberToStr(key.percent)} %</div>
                                    })
                                } else {
                                    return ""
                                }
                            }
                        });
                    }
                    if (temp.isActiveQty) {
                        tempColumnData[key].qty.push({
                            Header: () => <div className='text-center d-inline'>{TR(lang, columnTitles[`${temp.accessor}Qty`])} {getPeriod(date, i, lang)}</div>,
                            HeaderTitle: `${TR(lang, columnTitles[`${temp.accessor}Qty`])} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i + 1}.${temp.accessor}Qty`,
                            Cell: ({ value }) => {
                                return <div>{value ? NumberToStr(value) : 0}</div>;
                            }
                        });
                        if (isDiffPeriod(date.length, period) && false) {
                            tempColumnData[key].qty.push({
                                Header: () => <div className='text-center d-inline'>{i + 1}{TR(lang, "content.periodShort")} - {i}{TR(lang, "content.periodShort")}</div>,
                                HeaderTitle: `(${i + 1} - ${i})`,
                                accessor: `differenceQty.${temp.accessor}[${diffSelector(i)}]`,
                                Cell: ({ value }) => {
                                    return GetDiffferens(value);
                                }
                            });
                        }
                    }
                }
            });

        }

        let col = [{
            Header: () => <div className='text-center d-inline'>
                {TR(lang, "table.name")}
            </div>,
            HeaderTitle: TR(lang, "table.name"),
            accessor: 'name',
            role: 'name',
            serverSort: 'name',
            period: 1,
            minWidth: 400,
        }];
        if (location.pathname === '/analyze/drugs' && columnFilter.isOtc) {
            col.push({
                Header: () => <div className='text-center d-inline'> RX&OTC</div>,
                HeaderTitle: 'RX&OTC',
                accessor: "is_otc",
                location: 'rx_otc',
                Cell: ({ value }) => (value) ? 'OTC' : 'RX'
            })
        }
        for (const key in tempColumnData) {
            const temp = tempColumnData[key];
            if (temp.isPrice) {
                col.push(...temp.price, ...temp.price_uz, ...temp.qty, ...temp.percent_price, ...temp.percent_qty)
            } else {
                col.push(...temp.qty, ...temp.tops);
            }
        }
        setColumns([...col]);
        setColumnData({ ...tempColumnData })
        col = [];
        return tempColumnData;
    }
    const getAllList = async (total, date, dataIDList, selectedProductTypeIds, columnFilter, columnData, limit, page, sort, price, dataType, additional) => {
        setLoading(true);
        const obj = {
            dataType,
            is_active: true,
            deleted: false,
            filterByDate: date,
            filterCol: getFilterCol(columnFilter),
            dataIDList,
            dtID: selectedProductTypeIds,
            limit,
            sortBy: sort.key,
            sortByDesc: sort.value,
            sortPeriod: sort.period,
            ...additional
        }
        setParams(obj);
        const resp = await API.filter(page, obj);
        const result = changeData(resp.data.data, total, date, columnData, price);
        setData([...result]);
        setTotalPages(resp.data.total_pages);
        setLoading(false);
        return result;
    }
    const getTotalList = async (date, dataIDList, price, productTypeIds, dataType, additional) => {
        const resp = await AnalyzeApi.filterTotal({
            dataType,
            byTable: API.table,
            dataIDList,
            dtID: productTypeIds,
            is_active: true,
            deleted: false,
            filterByDate: date,
            ...additional
        })
        const result = changeTotal(resp.data.data[0], date, price);
        setTotal({ ...result });
        return result;
    }
    const changeTotal = (temp, date, price) => {
        let result = {
            ...temp,
            differencePrice: {
                price: [],
                price_uz: [],
                qty: []
            }
        };
        for (let i = 0; i < date.length; i++) {
            const period = i + 1;
            const currentElement = result[`period_${period}`];
            const lastElement = result[`period_${period - 1}`];
            if (isDiffPeriod(date.length, period) && false) {
                result.differencePrice.price.push((Number(currentElement[`${price}`]) || 0) - (Number(lastElement[`${price}`]) || 0));
                result.differencePrice.price_uz.push((Number(currentElement.UZS) || 0) - (Number(lastElement.UZS) || 0));
                result.differencePrice.qty.push((Number(currentElement.qty) || 0) - (Number(lastElement.qty) || 0));
            }
        }
        return result;
    }
    const changeData = (data, total, date, columnData, price) => {
        let sum_excel = {
            price: {
                price: [],
                price_uz: [],
                qty: []
            },
            differencePrice: {
                price: [],
                price_uz: [],
                qty: []
            }
        }
        let result = [];
        data?.map(element => {
            const difference = {
                price: [],
                price_uz: [],
                qty: [],
                percent_price: [],
                percent_qty: []

            };

            const differenceQty = {
                totalDrugNames: [],
                totalTrademarks: [],
                totalCompanies: [],
                totalDrugForms: [],
                totalDrugFormGroups: [],
                totalDrugTempGroups: [],
                totalManufacturers: [],
                totalDistributors: [],
                totalDrugInn: []
            }
            for (let i = 0; i < date.length; i++) {
                const period = i + 1;
                const c_p_k = `period_${period}`;
                const l_p_k = `period_${period - 1}`;
                const currentElement = element[c_p_k];
                const lastElement = element[l_p_k];
                for (const key in columnData) {
                    const temp = { ...columnData[key] };
                    if (!currentElement[`${temp.accessor}`]) { element[c_p_k][`${temp.accessor}`] = temp.default; }
                    if (temp.isPrice) {
                        if (!sum_excel.price.price[i]) sum_excel.price.price.push(0);
                        if (!sum_excel.price.price_uz[i]) sum_excel.price.price_uz.push(0);
                        if (!sum_excel.price.qty[i]) sum_excel.price.qty.push(0);
                        sum_excel.price.price[i] += +currentElement.totalCommonPerPrice[price] || 0;
                        sum_excel.price.price_uz[i] += +currentElement.totalCommonPerPrice.UZS || 0;
                        sum_excel.price.qty[i] += +currentElement.totalCommonPerPrice.qty || 0;
                        element[c_p_k].totalCommonPerPrice.percent_price = Math.round((10000 * currentElement.totalCommonPerPrice[price] / total[c_p_k][price])) / 100;
                        element[c_p_k].totalCommonPerPrice.percent_qty = Math.round((10000 * currentElement.totalCommonPerPrice.qty / total[c_p_k].qty)) / 100;
                    } else {
                        element[c_p_k][`${temp.accessor}`] = currentElement[`${temp.accessor}`].map(key => {
                            return {
                                ...key,
                                percent: Math.round((10000 * key[price] / currentElement.totalCommonPerPrice[price])) / 100
                            }
                        })
                    }
                    if (temp.isActivePrice || temp.isActiveQty) {
                        if (isDiffPeriod(date.length, period) && false) {
                            const index = diffSelector(i);
                            if (temp.isPrice) {
                                const current = currentElement.totalCommonPerPrice;
                                const last = lastElement.totalCommonPerPrice;
                                difference.price.push((Number(current[`${price}`]) || 0) - (Number(last[`${price}`]) || 0));
                                difference.price_uz.push((Number(current.UZS) || 0) - (Number(last.UZS) || 0));
                                difference.qty.push((Number(current.qty) || 0) - (Number(last.qty) || 0));
                                difference.percent_price.push(differencePercent(Number(last.percent_price) || 0, Number(current.percent_price) || 0));
                                difference.percent_qty.push(differencePercent(Number(last.percent_qty) || 0, Number(current.percent_qty) || 0));

                                if (!sum_excel.differencePrice.price[index]) sum_excel.differencePrice.price.push(0);
                                if (!sum_excel.differencePrice.price_uz[index]) sum_excel.differencePrice.price_uz.push(0);
                                if (!sum_excel.differencePrice.qty[index]) sum_excel.differencePrice.qty.push(0);

                                sum_excel.differencePrice.price[index] += difference.price[difference.price.length - 1];
                                sum_excel.differencePrice.price_uz[index] += difference.price_uz[difference.price_uz.length - 1];
                                sum_excel.differencePrice.qty[index] += difference.qty[difference.qty.length - 1];
                            } else {
                                differenceQty[temp.accessor].push((Number(currentElement[`${temp.accessor}Qty`]) || 0) - (Number(lastElement[`${temp.accessor}Qty`]) || 0))
                            }
                        }
                    }
                }
            }
            result.push({ ...element, differenceQty, differencePrice: difference });
        });
        setTotalExcel(JSON.parse(JSON.stringify(sum_excel)));
        return result;
    }

    const handleSearch = (temp, dataIds, productTypeIds, dataOptions, tempDataType, others) => {
        setLoading(true);
        const temp_additional = { region_id: others.region_id, district_id: others.district_id }
        setAdditional(temp_additional)
        setOthers(others)
        setDataType(tempDataType)
        setDate([...temp]);
        setSelectedProductTypeIds([...productTypeIds])
        setDataIDList([...dataIds]);
        setDataIdOptions([...dataOptions])
        const tempColumnFilter = (tempDataType === 2) ? { ...columnFilter, totalCompanies: false, totalCompaniesQty: false } : columnFilter
        setColumnFilter(tempColumnFilter)
        const columnData = createColumns(price, temp, tempColumnFilter);
        getTotalList(temp, dataIds, price, productTypeIds, tempDataType, temp_additional).then((total) => {
            getAllList(total, temp, dataIds, productTypeIds, tempColumnFilter, columnData, limit, page, sort, price, tempDataType, temp_additional);
        }).catch(() => { });
        setToggle(false);
    }
    const gotoPage = (page) => {
        setPage(page);
        getAllList(total, date, dataIDList, selectedProductTypeIds, columnFilter, columnData, limit, page, sort, price, dataType, additional);
    };
    const handleLimit = (value) => {
        setPage(1);
        setLimit(value);
        getAllList(total, date, dataIDList, selectedProductTypeIds, columnFilter, columnData, value, 1, sort, price, dataType, additional);
    };
    const handleColumnFilter = (obj) => {
        setColumnFilter({ ...obj });
        const columnData = createColumns(price, date, obj);
        getAllList(total, date, dataIDList, selectedProductTypeIds, obj, columnData, limit, page, sort, price, dataType, additional);
    }
    const handleSort = (key, value, period) => {
        setSort({ key, value, period });
        getAllList(total, date, dataIDList, selectedProductTypeIds, columnFilter, columnData, limit, page, { key, value, period }, price, dataType, additional);
    }
    const getFilterCol = (columnFilter) => {
        const temp = {};
        for (const key in columnFilter) {
            temp[key] = columnFilter[key];
        }
        return temp;
    }
    const setParams = (obj) => {
        const search = buildParams(obj);
        history.push({ pathname: location.pathname, search });
    }
    const getParams = () => {
        const obj = parseParams(location.search)
        return obj;
    }

    useEffect(() => {
        const def = getParams();
        if (Object.keys(def).length === 0) {
            const table = getThisKeys(location.pathname);
            ActivityApi.getActivity(table[3]).then(res => {
                let data = res.data.data?.body || {};
                data = JSON.parse(data);
                setDefault(data.limit, data.sortBy, data.sortByDesc, data.sortPeriod, data.filterByDate, data.dataIDList, data.dtID, data.filterCol, def.dataType, {
                    region_id: def.region_id ? Number(def.region_id) : null,
                    district_id: def.district_id ? Number(def.district_id) : null,
                })
            }).catch(err => {
                setDefault();
            })
        } else {
            setDefault(def.limit, def.sortBy, def.sortByDesc, def.sortPeriod, def.filterByDate, def.dataIDList, def.dtID, def.filterCol, def.dataType, {
                region_id: def.region_id ? Number(def.region_id) : null,
                district_id: def.district_id ? Number(def.district_id) : null,
            })
        }
    }, []);
    useEffect(() => {
        createColumns(price, date, columnFilter);
    }, [lang, date]);
    return <>
        <AnalyzeDataTable
            API={API}
            columns={columns}
            columnFilter={columnFilter}
            columnTitles={columnTitles}
            handleColumnFilter={handleColumnFilter}
            data={data}
            total={total}
            totalExcel={totalExcel}
            sort={sort}
            handleSort={handleSort}
            date={date}
            setDate={setDate}
            title={title}
            loading={loading}
            gotoPage={gotoPage}
            handleLimit={handleLimit}
            totalPages={totalPages}
            page={page}
            limit={limit}
            toggle={toggle}
            setToggle={setToggle}
            price={price}
            handleSearch={handleSearch}
            handleReboot={handleReboot}
            dataIDList={dataIDList}
            selectedProductTypeIds={selectedProductTypeIds}
            dataIdOptions={dataIdOptions}
            dataType={dataType}
            others={others}
        />
    </>

}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        lastUpdateDate: state.main.lastUpdateDate
    };
};

export default connect(mapStateToProps)(Analyze);