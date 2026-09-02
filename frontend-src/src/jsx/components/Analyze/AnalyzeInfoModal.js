import React, { useState, useEffect } from 'react'
import { Modal } from 'react-bootstrap'
import { DateFormat, NumberToStr } from '../../../utils';
import { TR } from '../../../utils/helpers';
import AnalyzeInfoTable from './AnalyzeInfoTable';
export default function AnalyzeInfoModal(props) {
    const {
        API,
        date,
        show,
        setShow,
        lang,
        selectedItem,
        dataID,
        title,
        selectedProductTypeIds,
        dataType,
        others
    } = props;

    const getThisKeys = () => {
        let arr = []
        if (API.name == 'drug') arr = ['cruds_country_mf', 'table_med']; else
            if (API.name == 'df') arr = ['cruds_country_mf', 'table_drugf']; else
                if (API.name == 'company') arr = ['cruds_country_mf', 'table_supplier']; else
                    if (API.name == 'dist') arr = ['cruds_country_mf', 'table_dis']; else
                        if (API.name == 'mf') arr = ['cruds_country', 'table_manuf']; else
                            if (API.name == 'trademark') arr = ['cruds_country_mf', 'table_trade']; else
                                if (API.name == 'inn') arr = ['cruds_country_mf', 'products_mnn']; else
                                    if (API.name == 'dfg') arr = ['cruds_country_mf', 'table_farmd']; else
                                        if (API.name == 'tpg') arr = ['cruds_country_mf', 'table_terd'];
        if (dataType === 2) {
            arr.push("table_serialNum", "table_supplier", "table_shelfLife")
        }
        return arr
    }
    const columnTitles = _.omit({
        // table_name: "table.name",
        cruds_country_mf: "cruds.country",
        table_med: `table.med`,
        table_drugf: "table.drugf",
        table_supplier: "table.supplier",
        table_dis: "table.dis",
        table_manuf: "table.manuf",
        cruds_country: "cruds.country",
        table_trade: "table.trade",
        products_mnn: "products.mnn",
        table_farmd: "table.farmd",
        table_terd: "table.terd",
        table_serialNum: "table.serialNum",
        table_shelfLife: "table.shelfLife",
        cruds_date40: "cruds.date40",
        table_qty: "table.qty",
        table_price: "table.price",
        table_priceSum: "table.priceSum",
        table_count: "table.count",
        table_countSum: "table.countSum",
    }, getThisKeys())
    const columnActions = _.omit({
        // table_name: true,
        cruds_country_mf: true,
        table_med: true,
        table_drugf: true,
        table_supplier: true,
        table_dis: true,
        table_manuf: true,
        cruds_country: true,
        table_trade: true,
        products_mnn: true,
        table_farmd: true,
        table_terd: true,
        table_serialNum: true,
        table_shelfLife: true,
        cruds_date40: true,
        table_qty: true,
        table_price: true,
        table_priceSum: true,
        table_count: true,
        table_countSum: true,
    }, getThisKeys());

    const defaultColumns = _.omit({
        table_name: {
            name: columnTitles.table_name,
            accessor: "name_uz",
            isActive: columnActions.table_name
        },
        cruds_country_mf: {
            name: columnTitles.cruds_country_mf,
            accessor: "country",
            isActive: columnActions.cruds_country_mf
        },
        table_med: {
            name: columnTitles.table_med,
            accessor: "drug_name",
            isActive: columnActions.table_med
        },
        table_drugf: {
            name: columnTitles.table_drugf,
            accessor: "drug_form",
            isActive: columnActions.table_drugf
        },
        table_supplier: {
            name: columnTitles.table_supplier,
            accessor: "sender_company",
            isActive: columnActions.table_supplier
        },
        table_dis: {
            name: columnTitles.table_dis,
            accessor: "distributor",
            isActive: columnActions.table_dis
        },
        table_manuf: {
            name: columnTitles.table_manuf,
            accessor: "manufacturer",
            isActive: columnActions.table_manuf
        },
        cruds_country: {
            name: columnTitles.cruds_country,
            accessor: "country",
            isActive: columnActions.cruds_country
        },
        table_trade: {
            name: columnTitles.table_trade,
            accessor: "trademark",
            isActive: columnActions.table_trade
        },
        products_mnn: {
            name: columnTitles.products_mnn,
            accessor: "drug_inn",
            isActive: columnActions.products_mnn
        },
        table_farmd: {
            name: columnTitles.table_farmd,
            accessor: "drug_farm_group",
            isActive: columnActions.table_farmd
        },
        table_terd: {
            name: columnTitles.table_terd,
            accessor: "drug_ts_group",
            isActive: columnActions.table_terd
        },
        table_serialNum: {
            name: columnTitles.table_serialNum,
            accessor: "serial_number",
            isActive: columnActions.table_serialNum
        },
        table_shelfLife: {
            name: columnTitles.table_shelfLife,
            accessor: "shelf_life",
            isActive: columnActions.table_shelfLife
        },
        cruds_date40: {
            name: columnTitles.cruds_date40,
            accessor: "mode_40_date",
            isActive: columnActions.cruds_date40
        },
        table_qty: {
            name: columnTitles.table_qty,
            accessor: "quantity",
            isActive: columnActions.table_qty
        },
        table_price: {
            name: columnTitles.table_price,
            accessor: "price_usd",
            isActive: columnActions.table_price
        },
        table_priceSum: {
            name: columnTitles.table_priceSum,
            accessor: "price_usd",
            isActive: columnActions.table_priceSum
        },
        table_count: {
            name: columnTitles.table_count,
            accessor: "sum_price_usd",
            isActive: columnActions.table_count
        },
        table_countSum: {
            name: columnTitles.table_countSum,
            accessor: "sum_price_uzs",
            isActive: columnActions.table_countSum
        }
    }, getThisKeys());

    const [columnFilter, setColumnFilter] = useState(columnActions);


    const [data, setData] = useState([]);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [active, setActive] = useState(0);
    const [loading, setLoading] = useState(true);
    const [total, setTotal] = useState({});
    const [totalExcel, setTotalExcel] = useState({ USD: 0, UZS: 0, qty: 0 })
    const changingData = (data) => {
        let temp = [], total_excel = { USD: 0, UZS: 0, qty: 0 };
        data.forEach((element, index) => {
            total_excel.USD += Number(element.sum_price_usd);
            total_excel.UZS += Number(element.sum_price_uzs);
            total_excel.qty += Number(element.quantity);
            temp.push({
                ...element,
                counter: index + 1,
                shelf_life: DateFormat(element.shelf_life),
                mode_40_date: DateFormat(element.mode_40_date)
            })
        })
        setTotalExcel(JSON.parse(JSON.stringify(total_excel)));
        setData([...temp]);
    }
    const getList = (index) => {
        setLoading(true);
        createColumns(columnFilter);
        const temp = {
            dataType,
            region_id: others.region_id ? Number(others.region_id) : null,
            district_id: others.district_id ? Number(others.district_id) : null,
            is_active: true,
            deleted: false,
            dtID: selectedProductTypeIds,
            dataID,
            fromDate: date[index].fromDate,
            toDate: date[index].toDate,
            limit
        }
        setActive(index);
        setTotal({ ...selectedItem[`period_${index + 1}`]?.totalCommonPerPrice })
        API.filterById(page, temp).then(res => {
            setTotalPages(res.data.pagination.total_pages)
            changingData(res.data.data)
            setLoading(false);
        }).catch(err => {
            setLoading(false);
        })
    }
    useEffect(() => {
        if (show) {
            getList(active);
        } else {
            setData([]);
        }
    }, [show, page, limit])

    const [columns, setColumns] = useState([])
    const gotoPage = (page) => {
        setPage(page);
    };
    const handleLimit = (value) => {
        setPage(1);
        setLimit(value);
    };
    const handleColumnFilter = (obj) => {
        setColumnFilter({ ...obj });
        createColumns(obj);
    }
    const completeCols = (columnFilter) => {
        const temp = JSON.parse(JSON.stringify(defaultColumns));
        for (const key in defaultColumns) {
            temp[key].isActive = columnFilter[key];
        }
        return { ...temp };
    }
    const createColumns = (columnFilter) => {
        const tempColumnData = JSON.parse(JSON.stringify(completeCols(columnFilter)));
        let col = [{
            Header: TR(lang, 'table.name'),
            accessor: 'name_uz',
        }];
        for (const key in tempColumnData) {
            const temp = tempColumnData[key];
            if (temp.isActive) {

                if (key === "table_price") {
                    col.push({
                        Header: TR(lang, "table.price"),
                        accessor: 'price_usd',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if (key === "table_priceSum") {
                    col.push({
                        Header: TR(lang, "table.priceSum"),
                        accessor: 'price_uzs',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if (key === "table_qty") {
                    col.push({
                        Header: TR(lang, "table.qty"),
                        accessor: 'quantity',
                        excel_total_accessor: 'qty',
                        total_accessor: 'qty',
                        role: 'count',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    },)
                } else if (key === "table_count") {
                    col.push({
                        Header: TR(lang, "table.count"),
                        accessor: 'sum_price_usd',
                        total_accessor: 'USD',
                        excel_total_accessor: 'USD',
                        role: 'price',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if (key === "table_countSum") {
                    col.push({
                        Header: TR(lang, "table.countSum"),
                        accessor: 'sum_price_uzs',
                        total_accessor: 'UZS',
                        excel_total_accessor: 'UZS',
                        role: 'price',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else {
                    col.push({
                        Header: TR(lang, temp.name),
                        accessor: temp.accessor,
                    })
                }
            }
        }
        setColumns([...col]);
        col = [];
        return tempColumnData;
    }
    return (
        <Modal
            dialogClassName='modal-dialog-info'
            show={show}
            onHide={setShow}
        >
            <div className="modal-header">
                <h4 className='m-0'>{TR(lang, "table.alsoInfo")}</h4>
                <button type="button" className="btn-close" onClick={() => setShow(false)} data-dismiss="modal"><span></span></button>
            </div>
            <div className="modal-body p-0">
                <AnalyzeInfoTable
                    title={title}
                    columns={columns}
                    getList={getList}
                    columnTitles={columnTitles}
                    columnFilter={columnFilter}
                    handleColumnFilter={handleColumnFilter}
                    loading={loading}
                    data={data}
                    total={total}
                    totalExcel={totalExcel}
                    date={date}
                    active={active}
                    limit={limit}
                    page={page}
                    totalPages={totalPages}
                    gotoPage={gotoPage}
                    handleLimit={handleLimit}
                    lang={lang}
                />
            </div>
        </Modal>

    )
}
