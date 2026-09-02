import { useEffect } from "react";
import { useState } from "react";
import { connect } from "react-redux";
import { differencePercent, diffSelector, GetDiffferens, getThisYear, isDiffPeriod, isInDate, NumberToStr, stringToDate } from "../../utils";
import { TR } from "../../utils/helpers";
import API from '../../services/SearchService'
import SearchDataTable from "../components/Search/SearchDataTable";
import DrugsApi from '../../services/cruds/DrugsService'
import DistApi from '../../services/cruds/DistributorService'
import MfApi from '../../services/cruds/ManufacturerService'
import CompanyApi from '../../services/cruds/CompanyService'
import DFApi from '../../services/cruds/DFormService'
import InnApi from '../../services/cruds/InnService'
import TpgApi from '../../services/cruds/TGroupService'
import DfgApi from '../../services/cruds/DFarmGroupService'
import TrademarkApi from '../../services/cruds/TradeMarkService'
import CountryApi from '../../services/cruds/CountryService'
const columnTitles = {
    totalCommonPerPrice: 'table.turnover',
    totalQuantity: 'table.turnOverCompPac',
    percentPrice: 'table.perc_price',
    percentQuantity: 'table.perc_qty',
    // totalDistributors: 'products.dist',
    // totalCompanies: 'products.senders',
    totalDrugInn: 'products.mnn',
    totalTrademarks: 'products.td',
    totalDrugForms: 'products.df',
    totalDrugFormGroups: 'products.dfg',
    totalDrugTempGroups: 'products.tpg',
    totalManufacturers: 'products.mf',
    totalCountry: 'products.country'
}
const columnActions = {
    totalCommonPerPrice: true,
    totalQuantity: true,
    percentQuantity: true,
    percentPrice: true,
    // totalDistributors: true,
    // totalCompanies: true,
    totalDrugInn: true,
    totalTrademarks: true,
    totalDrugForms: true,
    totalDrugFormGroups: true,
    totalDrugTempGroups: true,
    totalManufacturers: true,
    totalCountry: true
};
const defaultColumns = {
    totalCommonPerPrice: {
        accessor: `totalCommonPerPrice`, 
        value: [],
        isPrice: true,
        isActive: columnActions.totalCommonPerPrice,
        default: {
            sum_price_usd: 0,
            sum_price_uzs: 0,
            sum_price_eur: 0,
            sum_price_rub: 0,
            quantity: 0
        }, 
    },
    percentPrice: {
        accessor: `percent_price`, 
        value: [],
        isPricePercent: true,
        isActive: columnActions.percentPrice,
        default: {}, 
    },
    totalQuantity: {
        accessor: `quantity`, 
        value: [],
        isQuantity: true,
        isActive: columnActions.totalQuantity,
        default: {}, 
    },
    percentQuantity: {
        accessor: `percent_qty`, 
        value: [],
        isQtyPercent: true,
        isActive: columnActions.percentQuantity,
        default: {}, 
    },
    totalDrugInn: {
        name: 'products.mnn',
        accessor: `drug_inn`,
        role: 'text',
        value: [],
        isActive: columnActions.totalDrugInn
    },
    totalTrademarks: {
        name: 'products.td',
        accessor: `trademark`,
        role: 'text',
        value: [],
        isActive: columnActions.totalTrademarks
    },
    totalDrugForms: {
        name: 'products.df',
        accessor: `drug_form`, 
        role: 'text',
        value: [],
        isActive: columnActions.totalDrugForms
    },
    totalDrugFormGroups: {
        name: 'products.dfg',
        accessor: `drug_farm_group`, 
        role: 'text',
        value: [],
        isActive: columnActions.totalDrugFormGroups
    },
    totalDrugTempGroups: {
        name: 'products.tpg',
        accessor: `drug_ts_group`, 
        role: 'text',
        value: [],
        isActive: columnActions.totalDrugTempGroups
    },
    totalManufacturers: {
        name: 'products.mf',
        accessor: `manufacturer`, 
        role: 'text',
        value: [],
        isActive: columnActions.totalManufacturers
    },
    totalCountry: {
        name: 'products.country',
        accessor: `country`, 
        role: 'text',
        value: [],
        isActive: columnActions.totalManufacturers
    },
}
const defaultList = {
    drugID: [],
    distID: [],
    companyID: [],
    mfID: [],
    dfID: [],
    dfgID: [],
    innID: [],
    dtgID: [],
    dtID: [],
    trademarkID: [],
    countryID: [],
}
function Search(props) {
    const { lang, lastUpdateDate } = props;
    const title = 'sidebar.SearchOld';
    const api_list = [
        {
            key: 'drugID',
            api: DrugsApi,
            title: TR(lang, 'products.med'),
        },
        {
            key: 'distID',
            api: DistApi,
            title: TR(lang, 'products.dist'),
        },
        {
            key: 'companyID',
            api: CompanyApi,
            title: TR(lang, 'products.senders'),
        },
        {
            key: 'mfID',
            api: MfApi,
            title: TR(lang, 'products.mf'),
        },
        {
            key: 'dfID',
            api: DFApi,
            title: TR(lang, 'products.df'),
        },
        {
            key: 'innID',
            api: InnApi,
            title: TR(lang, 'products.mnn'),
        },
        {
            key: 'dtgID',
            api: TpgApi,
            title: TR(lang, 'products.tpg'),
        },
        {
            key: 'dfgID',
            api: DfgApi,
            title: TR(lang, 'products.dfg'),
        },
        {
            key: 'trademarkID',
            api: TrademarkApi,
            title: TR(lang, 'products.td'),
        },
        {
            key: 'countryID',
            api: CountryApi,
            title: TR(lang, 'products.country'),
        },
    ]
    const [columnData, setColumnData] = useState(defaultColumns);
    const [columnFilter, setColumnFilter] = useState(columnActions);
    const [toggle, setToggle] = useState(false);
    const [sort, setSort] = useState({key: 'sum_price_usd', value: true, period: 1});
    const [date, setDate] = useState([getThisYear(lastUpdateDate)]);
    const [dataIDList, setDataIDList] = useState({...defaultList});
    const [dataIdOptions, setDataIdOptions] = useState({...defaultList});
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [data, setData] = useState([]);
    const [total, setTotal] = useState({});
    const [price, setPrice] = useState("sum_price_usd");
    const [columns, setColumns] = useState([]);
    const priceOptions = [{value: "sum_price_usd", label:"$"},{value: "sum_price_eur", label:"€"},{value: "sum_price_rub", label:"₽"},{value: "sum_price_uzs", label:"uzs"}];    const [totalExcel, setTotalExcel] = useState({
        price: {
            price: [],
            price_uz: [],
            qty: []
        },
        difference: {
            price: [],
            price_uz: [],
            qty: []
        }
    })

    const getPeriod = (date, i, lang) =>{
        return date.length > 1 ? `${i+1}-${TR(lang, "content.periodShort")}.`: "";
    }

    const handleReboot = () => {
        const date = [getThisYear(lastUpdateDate)];
        const price = "sum_price_usd";
        const sort = {key: 'sum_price_usd', value: true, period: 1};
        setColumns([]);
        setData([]);
        setTotal({});
        setPrice(price);
        setPage(1);
        setDate(date);
        setLimit(25);
        setColumnData({...defaultColumns});
        setColumnFilter({...columnActions});
        setDataIDList({...defaultList});
        setSort(sort);
        
        setLoading(true);
        getTotalList(date, dataIDList).then((total) => {
            const columnData = createColumns(price, date, columnActions);
            getAllList(total, date, defaultList, columnActions, columnData, 25, 1, sort, price);
        }).catch(() => {});
    }
    const handleChangePrice = (value, date) => {
        setLoading(true);
        setPrice(value);
        const columnData = createColumns(value, date, columnFilter);
        getTotalList(date, dataIDList).then((total) => {
            getAllList(total, date, dataIDList, columnFilter, columnData, limit, 1, sort, value)
        }).catch(() => {});
    }
    const completeCols = (columnFilter) => { 
        const temp = JSON.parse(JSON.stringify(defaultColumns));
        for (const key in defaultColumns) {
            temp[key].isActive = columnFilter[key]
        }
        return {...temp};
    }
    const createColumns = (price, date, columnFilter) => {
        const tempColumnData = JSON.parse(JSON.stringify(completeCols(columnFilter)));
        for (const key in tempColumnData) {
            const temp = {...tempColumnData[key]};
            date.forEach((_, i) => {
                const period = i +1;
                if(temp.isActive){
                    if(temp.isPrice) {
                        tempColumnData[key].value.push({
                            Header: () => <div className='text-center d-inline'>
                                {`${TR(lang, "table.turnover")}`} {`${TR(lang, "table.turnoverUZB")}`} {getPeriod(date, i, lang)}
                                <select
                                    style={{width: "80px"}}
                                    className="form-select ms-2"
                                    value={price}
                                    onChange={(e) => handleChangePrice(e.target.value, date)}
                                >
                                    {priceOptions.map(ccy => {
                                        return <option key = {ccy.value} value={ccy.value}>{ccy.label}</option>
                                    })}
                                </select>
                            </div>,
                            HeaderTitle: `${TR(lang, "table.turnover")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i+1}.${temp.accessor}.${price}`,
                            role: 'price',
                            total_accessor: `period_${i+1}.${price}`,
                            excel_total_accessor: `price.price[${i}]`,
                            serverSort: 'sum_price_usd',
                            period: i+1,
                            Cell:({value}) => {
                                return <div>{value ? NumberToStr(value) : 0}</div>;
                            }
                        });
                        
                        if(isDiffPeriod(date.length, period)){
                            tempColumnData[key].value.push({
                                Header: () => <div className='text-nowrap text-center d-inline'>({i+1} - {i})</div>,
                                HeaderTitle: `(${i+1} - ${i})`,
                                accessor: `difference.price[${diffSelector(i)}]`,
                                role: 'diffPrice.price',
                                total_accessor: `difference.price[${diffSelector(i)}]`,
                                excel_total_accessor: `difference[${price}][${diffSelector(i)}]`,
                                Cell:({value}) => {
                                    return GetDiffferens(value, 2);
                                }
                            });
                        }
                    } else if(temp.isQuantity) {
                        tempColumnData[key].value.push({
                            Header: () => <div className='text-center d-inline'>
                                {TR(lang, "table.turnOverCompPac")} {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.turnOverCompPac")} ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i+1}.totalCommonPerPrice.${temp.accessor}`,
                            role: 'count',
                            total_accessor: `period_${i+1}.quantity`,
                            excel_total_accessor: `price.qty[${i}]`,
                            serverSort: 'quantity',
                            period: i+1,
                            Cell:({value}) => {
                                return <div>{value ? NumberToStr(value) : 0}</div>;
                            }
                        });
                        if(isDiffPeriod(date.length, period)){
                            tempColumnData[key].value.push({
                                Header: () => <div className='text-center d-inline'>({i+1} - {i})</div>,
                                HeaderTitle: `(${i+1} - ${i})`,
                                accessor: `difference.qty[${diffSelector(i)}]`,
                                role: 'diffPrice.qty',
                                total_accessor: `difference.qty[${diffSelector(i)}]`,
                                excel_total_accessor: `difference.qty[${diffSelector(i)}]`,
                                Cell:({value}) => {
                                    return GetDiffferens(value);
                                }
                            });
                        }
                    } else if(temp.isPricePercent) {
                        tempColumnData[key].value.push({
                            Header: () => <div className='text-center d-inline'>
                                {TR(lang, "table.perc_price")}  {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.perc_price")}  ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i+1}.totalCommonPerPrice.${temp.accessor}`,
                            role: 'percent',
                            minWidth: 90,
                            Cell:({value}) => {
                                return <div className="text-nowrap">{value ? NumberToStr(Number(value.toFixed(value > 1?2:4))) : 0} %</div>;
                            }
                        });
                        if(isDiffPeriod(date.length, period)){
                            tempColumnData[key].value.push({
                                Header: () => <div className='text-nowrap text-center d-inline'>({i+1} - {i})</div>,
                                HeaderTitle: `(${i+1} - ${i})`,
                                role: 'dif_percent',
                                accessor: `difference.percent_price[${diffSelector(i)}]`,
                                Cell:({value}) => {
                                    return <div>{GetDiffferens(value, value > 1?2:4, '%')}</div>;
                                }
                            });
                        }
                    } else if(temp.isQtyPercent) {
                        tempColumnData[key].value.push({
                            Header: () => <div className='text-center d-inline'>
                                {TR(lang, "table.perc_qty")}  {getPeriod(date, i, lang)}
                            </div>,
                            HeaderTitle: `${TR(lang, "table.perc_qty")}  ${getPeriod(date, i, lang)}`,
                            accessor: `period_${i+1}.totalCommonPerPrice.${temp.accessor}`,
                            role: 'percent',
                            minWidth: 90,
                            Cell:({value}) => {
                                return <div className="text-nowrap">{value ? NumberToStr(Number(value.toFixed(value > 1?2:4))) : 0} %</div>;
                            }
                        });
                        if(isDiffPeriod(date.length, period)){
                            tempColumnData[key].value.push({
                                Header: () => <div className='text-nowrap text-center d-inline'>({i+1} - {i})</div>,
                                HeaderTitle: `(${i+1} - ${i})`,
                                role: 'dif_percent',
                                accessor: `difference.percent_qty[${diffSelector(i)}]`,
                                Cell:({value}) => {
                                    return <div className="text-nowrap">{GetDiffferens(value, value > 1?2:4, '%')}</div>;
                                }
                            })
                        }
                    }
                }
            });
            if(temp.role === 'text' && temp.isActive){
                tempColumnData[key].value.push({
                    Header: () => <div className='text-center d-inline'>
                        {TR(lang, temp.name)}
                    </div>,
                    HeaderTitle: TR(lang, temp.name),
                    accessor: temp.accessor,
                    minWidth: temp.accessor === "mode_40_date" ? 150: null
                });
            }
        }
        let col = [{
            Header: () => <div className='text-center d-inline'>
                {TR(lang,"products.med")}
            </div>,
            HeaderTitle: TR(lang,"products.med"),
            accessor: 'drug_name',
            role: 'name',
            serverSort: 'name',
            period: 1,
            minWidth: 400,
        },
        {
            Header: () => <div className='text-center d-inline'> RX&OTC</div>,
            HeaderTitle: 'RX&OTC',
            accessor: "is_otc",
            location: 'rx_otc',
            Cell: ( {value} ) => (value) ? 'OTC' : 'RX'
        }
        ];
        for (const key in tempColumnData) {
            const temp = tempColumnData[key];
            col.push(...temp.value)
        }
        setColumns([...col]);

        setColumnData({...tempColumnData})
        col = [];
        return tempColumnData;
    }
    const getAllList = async (total, date, dataIDList, columnFilter, columnData, limit, page, sort, price) => {
        setLoading(true);
        const obj = {
            is_active: true,
            is_deleted: false,
            filterByDate: date,
            filterCol: getFilterCol(columnFilter),
            ...dataIDList,
            limit,
            sortBy: sort.key,
            sortByDesc: sort.value
        }
        const resp = await API.filterGroup(page, obj);
        const result = changeData(resp.data.data, total, date, columnData, price);
        setData([...result]);
        setTotalPages(resp.data.total_pages);
        setLoading(false);
        return result;
    }
    const getTotalList = async (date, dataIDList) => {
        const resp = await API.filterTotal({
            is_active: true,
            deleted: false,
            filterByDate: date,
            ...dataIDList
        })
        const result = changeTotal(resp.data.data[0], date);
        setTotal({...result});
        return result;
    }
    const changeTotal = (temp, date) => {
        let result = {
            ...temp,
            difference: {
                sum_price_usd: [],
                sum_price_eur: [],
                sum_price_rub: [],
                price_uz: [],
                qty: []
            }
        };
        for (let i = 0; i < date.length; i++) {
            const period = i+1;
            const currentElement = result[`period_${period}`];
            const lastElement = result[`period_${period-1}`];

            if(isDiffPeriod(date.length, period) ){
                result.difference.sum_price_usd.push((Number(currentElement.sum_price_usd) || 0) - (Number(lastElement.sum_price_usd) || 0));
                result.difference.sum_price_eur.push((Number(currentElement.sum_price_eur) || 0) - (Number(lastElement.sum_price_eur) || 0));
                result.difference.sum_price_rub.push((Number(currentElement.sum_price_rub) || 0) - (Number(lastElement.sum_price_rub) || 0));
                result.difference.price_uz.push((Number(currentElement.sum_price_uzs) || 0) - (Number(lastElement.sum_price_uzs) || 0));
                result.difference.qty.push((Number(currentElement.quantity) || 0) - (Number(lastElement.quantity) || 0));
            }
            
        }
        return result;
    }
    const changeData = (data, total, date, columnData, price) =>{
        let sum_excel = {
            price: {
                price: [],
                price_uz: [],
                qty: []
            },
            difference: {
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
            
            for (let i = 0; i < date.length; i++) {
                const period = i+1;
                const c_p_k = `period_${period}`;
                const l_p_k = `period_${period-1}`;
                const currentElement = element[c_p_k];
                const lastElement = element[l_p_k];
                for (const key in columnData) {
                    const temp = {...columnData[key]};
                    if(temp.isPrice){
                        if(!currentElement[`${temp.accessor}`]){element[c_p_k][`${temp.accessor}`] = temp.default;}
                        if(!sum_excel.price.price[i]) sum_excel.price.price.push(0);
                        if(!sum_excel.price.price_uz[i]) sum_excel.price.price_uz.push(0);
                        if(!sum_excel.price.qty[i]) sum_excel.price.qty.push(0);

                        sum_excel.price.price[i] += +currentElement.totalCommonPerPrice[price] || 0;
                        sum_excel.price.price_uz[i] += +currentElement.totalCommonPerPrice.sum_price_uzs || 0;
                        sum_excel.price.qty[i] += +currentElement.totalCommonPerPrice.quantity || 0;

                        element[c_p_k].totalCommonPerPrice.percent_price = Math.round((10000 * currentElement.totalCommonPerPrice.sum_price_usd / total[c_p_k].sum_price_usd )) / 100;
                        element[c_p_k].totalCommonPerPrice.percent_qty = Math.round((10000 * currentElement.totalCommonPerPrice.quantity / total[c_p_k].quantity )) / 100;
                        if(isDiffPeriod(date.length, period)){
                            const index = diffSelector(i);
                            const current = currentElement.totalCommonPerPrice;
                            const last = lastElement.totalCommonPerPrice;

                            difference.price.push((Number(current[`${price}`])||0) - (Number(last[`${price}`])||0));
                            difference.price_uz.push((Number(current.sum_price_uzs)||0) - (Number(last.sum_price_uzs)||0));
                            difference.qty.push((Number(current.quantity)||0) - (Number(last.quantity)||0));
                            difference.percent_price.push(differencePercent(Number(last.percent_price) || 0, Number(current.percent_price) || 0));
                            difference.percent_qty.push(differencePercent(Number(last.percent_qty) || 0, Number(current.percent_qty) || 0));
    
                            if(!sum_excel.difference.price[index]) sum_excel.difference.price.push(0);
                            if(!sum_excel.difference.price_uz[index]) sum_excel.difference.price_uz.push(0);
                            if(!sum_excel.difference.qty[index]) sum_excel.difference.qty.push(0);
    
                            sum_excel.difference.price[index] += difference.price[difference.price.length-1];
                            sum_excel.difference.price_uz[index] += difference.price_uz[difference.price_uz.length-1];
                            sum_excel.difference.qty[index] += difference.qty[difference.qty.length-1];
                        }
                    }
                }
            }
            result.push({...element, difference});
        });
        setTotalExcel(JSON.parse(JSON.stringify(sum_excel)));
        return result;
    }

    const handleSearch = (temp, dataIds, dataOptions) => {
        setLoading(true);
        setDate([...temp]);
        setDataIDList({...dataIds});
        setDataIdOptions({...dataOptions})
        const columnData = createColumns(price, temp, columnFilter);
        getTotalList(temp, dataIds).then((total) => {
            getAllList(total, temp, dataIds, columnFilter, columnData, limit, page, sort, price);
        }).catch();
        setToggle(false);
    }
    const gotoPage = (page) => {
        setPage(page);
        getAllList(total, date, dataIDList, columnFilter, columnData, limit, page, sort, price);
    };
    const handleLimit = (value) => {
        setPage(1);
        setLimit(value);
        getAllList(total, date, dataIDList, columnFilter, columnData, value, 1, sort, price);
    };
    const handleColumnFilter = (obj) => {
        setColumnFilter({...obj});
        const columnData = createColumns(price, date, obj);
        getAllList(total, date, dataIDList, obj, columnData, limit, page, sort, price);
    }
    const handleSort = (key, value, period) => {
        setSort({key, value, period});
        getAllList(total, date, dataIDList, columnFilter, columnData, limit, page, {key, value, period}, price);
    }
    const getFilterCol = (columnFilter) => {
        const temp = {};
        for (const key in columnFilter) {
            temp[key] = columnFilter[key];
        }
        return temp;
    }
    useEffect(() => {
        const columnData = createColumns(price, date, columnFilter);
        getTotalList(date, dataIDList).then((total) => {
            getAllList(total, date, dataIDList, columnFilter, columnData, limit, 1, sort, price);
        }).catch(() => {});
    },[]);
    useEffect(() => {
        createColumns(price, date, columnFilter);
    }, [lang, date]);
    return <SearchDataTable
        API={API}
        columns={columns}
        columnFilter={columnFilter}
        columnTitles={columnTitles}
        handleColumnFilter = {handleColumnFilter}
        data = {data}
        total = {total}
        sort = {sort}
        handleSort = {handleSort}
        date={date}
        setDate={setDate}
        title = {title}
        loading = {loading}
        gotoPage = {gotoPage}
        handleLimit={handleLimit}
        totalPages = {totalPages}
        page = {page}
        limit= {limit}
        toggle = {toggle}
        setToggle = {setToggle}
        price = {price}
        handleSearch={handleSearch}
        handleReboot = {handleReboot}
        api_list={api_list}
        defaultList={defaultList}
        dataIDList={dataIDList}
        dataIdOptions={dataIdOptions}
        totalExcel={totalExcel}
    />
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        lastUpdateDate: state.main.lastUpdateDate
    };
};

export default connect(mapStateToProps)(Search);