import React, {useState, useEffect} from 'react'
import {Modal} from 'react-bootstrap'
import {DateFormat, NumberToStr} from '../../../utils';
import { TR } from '../../../utils/helpers';
import SearchByIdTable from './SearchByIdTable';
export default function SearchByIdModal(props) {
    const columnTitles = {
        // table_name: "table.name",
        table_price: "table.price",
        table_qty: "table.qty",
        table_perc_qty: "table.perc_qty",
        table_count: "table.count",
        table_countSum: "table.countSum",
        table_perc_price: "table.perc_price",
        cruds_date40: "cruds.date40",
        table_dis: "table.dis",
        table_supplier: "table.supplier",
        products_mnn: "products.mnn",
        table_trade: "table.trade",
        table_drugf: "table.drugf",
        table_farmd: "table.farmd",
        table_terd: "table.terd",
        table_manuf: "table.manuf",
        cruds_country: "cruds.country",
    }
    const columnActions = {
        // table_name: true,
        table_price: true,
        table_qty: true,
        table_perc_qty: true,
        table_count: true,
        table_countSum: true,
        table_perc_price: true,
        cruds_date40: true,
        table_dis: true,
        table_supplier: true,
        products_mnn: true,
        table_trade: true,
        table_drugf: true,
        table_farmd: true,
        table_terd: true,
        table_manuf: true,
        cruds_country: true,
    };

    const defaultColumns = {
        "table_name": {
            name: "table.name",
            accessor:'name_uz',
            isActive: columnActions.table_name,
        },
        "table_price": {
            accessor:'price_usd',
            isActive: columnActions.table_price,
        },
        "table_qty": {
            accessor:'quantity',
            isActive: columnActions.table_qty,
        },
        "table_perc_qty": {
            accessor:`percent_qty`,
            isActive: columnActions.table_perc_qty,
        },
        "table_count": {
            accessor:'sum_price_usd',
            isActive: columnActions.table_count,
        },
        "table_countSum": {
            accessor:'sum_price_uzs',
            isActive: columnActions.table_countSum,
        },
        "table_perc_price": {
            accessor:`percent_price`,
            isActive: columnActions.table_perc_price,
        },
        "cruds_date40": {
            name: "cruds.date40",
            accessor:'mode_40_date',
            isActive: columnActions.cruds_date40,
        },
        "table_dis": {
            name: "table.dis",
            accessor:'distributor',
            isActive: columnActions.table_dis,
        },
        "table_supplier": {
            name: "table.supplier",
            accessor:'sender_company',
            isActive: columnActions.table_supplier,
        },
        "products_mnn": {
            name: "products.mnn",
            accessor:'drug_inn',
            isActive: columnActions.products_mnn,
        },
        "table_trade": {
            name: "table.trade",
            accessor:'trademark',
            isActive: columnActions.table_trade,
        },
        "table_drugf": {
            name: "table.drugf",
            accessor:'drug_form',
            isActive: columnActions.table_drugf,
        },
        "table_farmd": {
            name: "table.farmd",
            accessor:'drug_farm_group',
            isActive: columnActions.table_farmd,
        },
        "table_terd": {
            name: "table.terd",
            accessor:'drug_ts_group',
            isActive: columnActions.table_terd,
        },
        "table_manuf": {
            name: "table.manuf",
            accessor:'manufacturer',
            isActive: columnActions.table_manuf,
        },
        "cruds_country": {
            name: "cruds.country",
            accessor:'country',
            isActive: columnActions.cruds_country,
        },
    }
    // const [columnData, setColumnData] = useState(defaultColumns);
    const [columnFilter, setColumnFilter] = useState(columnActions);


    const {
        API,
        date,
        show,
        setShow,
        lang,
        selectedItem,
        dataID,
        mfID,
        dataIDList,
        selectedCheckbox,
        title
    } = props;
    const [data, setData] = useState([]);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [active, setActive] = useState(0);
    const [loading, setLoading] = useState(true);
    const [total, setTotal] = useState({});
    const [totalExcel, setTotalExcel] = useState({ sum_price_usd: 0, sum_price_uzs: 0, quantity: 0 })

    const [columns, setColumns] = useState([]);

    const changingData = (data, total) => {
        let temp = [], total_excel = { sum_price_usd: 0, sum_price_uzs: 0, quantity: 0 };
        data.forEach((element, index) => {
            total_excel.sum_price_usd += Number(element.sum_price_usd);
            total_excel.sum_price_uzs += Number(element.sum_price_uzs);
            total_excel.quantity += Number(element.quantity);
            temp.push({
                ...element,
                percent_price: Math.round((10000 * element.sum_price_usd / total.sum_price_usd)) / 100,
                percent_qty: Math.round((10000 * element.quantity / total.quantity)) / 100,
                counter: index + 1,
                shelf_life: DateFormat(element.shelf_life)
            })
        })
        setTotalExcel(JSON.parse(JSON.stringify(total_excel)));
        setData([...temp]);
    }
    const getList = (index) => {
        createColumns(columnFilter);
        setLoading(true);
        const temp = {
            is_active: true,
            deleted: false,
            fromDate: date[index].fromDate,
            toDate: date[index].toDate,
            limit,
            filterBy: selectedCheckbox,
            ...dataIDList,
            dataID,
        }
        setActive(index);
        const temp_total = { ...selectedItem[`period_${index + 1}`]?.totalCommonPerPrice };
        setTotal(temp_total)
        API.newFilterById(page, temp).then(res => {
            setTotalPages(res.data.pagination.total_pages)
            changingData(res.data.data, temp_total)
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
            if(temp.isActive) {

                if(key === "table_price") {
                    col.push({
                        Header: TR(lang, "table.price"),
                        accessor: 'price_usd',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if(key === "table_qty") {
                    col.push({
                        Header: TR(lang, "table.qty"),
                        accessor: 'quantity',
                        excel_total_accessor: 'quantity',
                        total_accessor: 'quantity',
                        role: 'count',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if(key === "table_perc_qty") {
                    col.push({
                        Header: () => <div className='text-center d-inline'>
                            {TR(lang, "table.perc_qty")}
                        </div>,
                        HeaderTitle: `${TR(lang, "table.perc_qty")}`,
                        accessor: `percent_qty`,
                        role: 'percent',
                        minWidth: 90,
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0} %</div>;
                        }
                    })
                } else if(key === "table_count") {
                    col.push({
                        Header: TR(lang, "table.count"),
                        accessor: 'sum_price_usd',
                        total_accessor: 'sum_price_usd',
                        excel_total_accessor: 'sum_price_usd',
                        role: 'price',
                        minWidth: 120,
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if(key === "table_countSum") {
                    col.push({
                        Header: TR(lang, "table.countSum"),
                        accessor: 'sum_price_uzs',
                        total_accessor: 'sum_price_uzs',
                        excel_total_accessor: 'sum_price_uzs',
                        role: 'price',
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0}</div>;
                        }
                    })
                } else if(key === "table_perc_price") {
                    col.push({
                        Header: () => <div className='text-center d-inline'>
                            {TR(lang, "table.perc_price")}
                        </div>,
                        HeaderTitle: `${TR(lang, "table.perc_price")}`,
                        accessor: `percent_price`,
                        role: 'percent',
                        minWidth: 90,
                        Cell: ({ value }) => {
                            return <div>{value ? NumberToStr(value) : 0} %</div>;
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
        // setColumnData({ ...tempColumnData })
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
                <button type="button" className="btn-close" onClick={()=> setShow(false)} data-dismiss="modal"><span></span></button>
            </div>
            <div className="modal-body p-0">
                <SearchByIdTable
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

        // {
        //     Header: TR(lang, `table.med`),
        //     accessor: 'drug_name',
        //     hide: API.name == 'drug'
        // },
        // ,
                