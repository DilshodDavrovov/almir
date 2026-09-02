import React, { useState, useEffect } from 'react';
import { connect, useDispatch } from 'react-redux';
import { Route, useHistory, useLocation } from 'react-router-dom';
import CrudTable from '../../../components/CrudTable/CrudTable';
import API from '../../../../services/cruds/DrcService'
import DeleteModal from '../../../components/Modals/DeleteModal';
import { DateFormat, NumberToStr, formatDate, getThisYear, showToast, stringToDate } from '../../../../utils';
import { saleTablePriceAction } from '../../../../store/actions/SaleAction';
import { Form, Modal, Button } from "react-bootstrap";
import { TR } from '../../../../utils/helpers';
import StatusModal from '../../../components/Modals/StatusModal';
import AnalyzeApi from '../../../../services/AnalyzeService'
import UpdateLogModal from '../../../components/Modals/UpdateLogModal';
import AddSale from './AddSale';
import EditSale from './EditSale';
const columnTitles = {
    'price': "table.price",
    'quantity': "table.qty",
    '_manufacturer.name': "products.mf",
    "distributor.name": "table.dist",
    'user': "content.user",
    'created_at': "cruds.created",
    "counterparty": 'products.counterparty'
}
const columnActions = {
    'price': true,
    'quantity': true,
    '_manufacturer.name': true,
    "distributor.name": false,
    'user': false,
    'created_at': false,
    'counterparty': false
};
const Sale = (props) => {
    const title = "products.sale";
    const dispatch = useDispatch();
    const history = useHistory();
    const { url } = props.match;
    const { lang, price, c_price, sum_price, productTypes, lastUpdateDate } = props;
    const priceOptions = [{ value: "usd", label: "$" }, { value: "eur", label: "€" }, { value: "rub", label: "₽" }, { value: "uzs", label: "SO'M" }];
    const [loading, setLoading] = useState(true);
    const [timer, setTimer] = useState(null)
    const [periodCode, setPeriodCode] = useState('');
    const [drugCode, setDrugCode] = useState('');
    const [multiSelectLoading, setMultiSelectLoading] = useState(false);
    const [sort, setSort] = useState({ key: null, value: true });
    const [ids, setIds] = useState([]);
    const [list, setList] = useState([]);
    const [selectedList, setSelectedList] = useState([]);
    const [uploadLoading, setUploadLoading] = useState(-1);
    const [uploadErrorList, setUploadErrorList] = useState([]);
    const [data, setData] = useState([]);
    const [delId, setDelId] = useState(null);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [filterStatus, setFilterStatus] = useState("active");
    const [delModal, setDelModal] = useState(false);
    const [statusModal, setStatusModal] = useState(false);
    const [uploadModal, setUploadModal] = useState(false);
    const [updateLogModal, setUpdateLogModal] = useState(false);
    const [selectedData, setSelectedData] = useState([]);
    const [selectedProductTypes, setSelectedProductTypes] = useState([]);
    const [productTypeIds, setProductTypeIds] = useState([]);
    const [dateRange, setDateRange] = useState([]);
    const [columnFilter, setColumnFilter] = useState(columnActions);
    const [columns, setColumns] = useState([
        {
            Header: "№",
            serverSort: 'period_code',
            accessor: 'period_code',
            name: 'period_code'
        },
        {
            Header: TR(lang, "table.name"),
            accessor: 'drug_name.name',
            name: 'drug_name.name',
            minWidth: 400
        },
        {
            HeaderTitle: `${TR(lang, "table.price")}`,
            Header: () => <div className='d-inline align-items-center justify-content-center '>
                {TR(lang, "table.price")}
                <select
                    style={{ width: "60px" }}
                    className="d-inline form-select ms-2"
                    value={price}
                    onChange={(e) => dispatch(saleTablePriceAction(e.target.value))}
                >
                    {priceOptions.map(ccy => {
                        return <option key={ccy.value} value={ccy.value}>{ccy.label}</option>
                    })}
                </select>
            </div>,
            serverSort: `price_${price}`,
            accessor: `price_${price}`,
            name: `price`,
            Cell: ({ value }) => {
                return value ? NumberToStr(value) : "";
            }
        },
        {
            Header: TR(lang, "table.qty"),
            serverSort: 'quantity',
            accessor: 'quantity',
            name: 'quantity',
        },
        {
            Header: TR(lang, "products.mf"),
            accessor: '_manufacturer.name',
            name: '_manufacturer.name'
        },
        {
            Header: TR(lang, "table.dist"),
            accessor: 'distributor.name',
            name: 'distributor.name',
        },

        {
            Header: TR(lang, "content.user"),
            accessor: "user",
            name: "user",
            location: 'drc_user',
            Cell: ({ value }) => {
                return `${value.first_name + " " + value.last_name}`
            }
        },
        {
            Header: TR(lang, "cruds.created"),
            serverSort: "createdAt",
            accessor: 'created_at',
            name: 'created_at'
        },
        {
            Header: TR(lang, "products.counterparty"),
            accessor: 'counterparty.name',
            name: 'counterparty'
        }
    ]);
    const handleAdd = () => {
        history.push("/admin/sale/add");
    }
    const handleEdit = (item) => {
        history.push(`/admin/sale/update/${item.id}`)
    };
    const handleDelete = (item) => {
        setDelModal(true);
        setDelId({ id: item.id, deleted: item.deleted });
    };
    const handleStatusChange = (item) => {
        setStatusModal(true);
        setDelId({ id: item.id });
    };
    const handleUpload = () => {
        setUploadModal(true);
    }
    const handleSelect = (id) => {
        const index = selectedData.indexOf(id);
        if (index > -1) {
            let temp = selectedData;
            temp.splice(index, 1);
            setSelectedData([...temp]);
        } else {
            let temp = selectedData;
            setSelectedData([...temp, id]);
        }
    };
    const handleSelectAll = () => {
        if (data.length === selectedData.length) {
            setSelectedData([])
        } else {
            setSelectedData([...data.map(key => key.id)]);
        }
    };
    const handleLimit = (limit) => {
        setLimit(limit);
        filter(page, limit, filterStatus, ids, sort, periodCode, productTypeIds, dateRange, drugCode)
    };
    const handleChangeUnicode = (e) => {
        setPeriodCode(e.target.value);
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            filter(page, limit, filterStatus, ids, sort, e.target.value, productTypeIds, dateRange, drugCode);
        }, 1000)
        setTimer(newTimer);
    }
    const handleChangeDrugCode = (e) => {
        setDrugCode(e.target.value);
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            filter(page, limit, filterStatus, ids, sort, periodCode, productTypeIds, dateRange, e.target.value);
        }, 1000)
        setTimer(newTimer);
    }
    const handleChangeDateRange = (e) => {
        setDateRange(e || []);
        if (
            !e ||
            e[0] && new Date('01-01-2000') < e[0] && e[0] < new Date('01-01-9999') ||
            e[1] && new Date('01-01-2000') < e[1] && e[1] < new Date('01-01-9999')
        ) {
            filter(page, limit, filterStatus, ids, sort, periodCode, productTypeIds, e || [], drugCode)
        }
    }
    const gotoPage = (page) => {
        setPage(page);
        filter(page, limit, filterStatus, ids, sort, periodCode, productTypeIds, dateRange, drugCode)
    };
    const handleFilterStatus = (filterStatus) => {
        setFilterStatus(filterStatus);
        filter(page, limit, filterStatus, ids, sort, periodCode, productTypeIds, dateRange, drugCode);
    }
    const filter = (page, limit, filterStatus, dataID, sort, periodCode, productTypeIds, dateRange, drugCode) => {
        setLoading(true);
        let temp = {};
        if (dateRange.length === 2) {
            temp = {
                fromDate: dateRange[0],
                toDate: dateRange[1]
            }
        }
        API.getList(page, limit, filterStatus == "active", filterStatus == "deleted", dataID, sort, periodCode, productTypeIds, temp, drugCode, 2)
            .then(resp => {
                setData(resp.data.data);
                setTotalPages(resp.data.pagination.total_pages);
                setLoading(false);
            });
    }

    const changeStatus = (id) => {
        API.changeStatus(id, { is_active: filterStatus !== "active", deleted: false }).then(res => {
            showToast('success', res.data.message);
            setLoading(true);
            getAllList();
        }).catch(err => {
            showToast('error', err.response.data.message);
        });
        setStatusModal(false);
    }

    const del = (id) => {
        if (filterStatus === "deleted") {
            API.softDelete(id).then(res => {
                showToast('success', res.data.message);
                setLoading(true);
                getAllList();
            }).catch(err => {
                showToast('error', err.response.data.message);
            })
            setDelModal(false);
        } else {
            API.changeStatus(id, { is_active: false, deleted: true }).then(res => {
                showToast('success', res.data.message);
                setLoading(true);
                getAllList();
            }).catch(err => {
                showToast('error', err.response.data.message);
            })
            setDelModal(false);
        }
    }

    const handleSort = (key, value) => {
        setSort({ key, value });
        filter(page, limit, filterStatus, ids, { key, value }, periodCode, productTypeIds, dateRange, drugCode);
    }

    const handleSubmitFile = (e) => {
        setUploadLoading(0);
        e.preventDefault();
        const formData = new FormData();
        formData.append('file', e.target[0].files[0]);
        formData.append('data_type', 2);
        API.uploadFile(formData, setUploadLoading).then(res => {
            if (res.status === 200) {
                showToast('success', res.data.message);
            }
            setUploadModal(false);
            setLoading(true);
            getAllList();
        }).catch(err => {
            const errData = err.response.data;
            if (Array.isArray(errData.error)) {
                if (errData.error.length > 1000) {
                    setUploadErrorList([
                        ...errData.error.slice(0, 1000),
                        { message: '.................................' }
                    ]);
                } else {
                    setUploadErrorList(errData.error.slice(0, 1000));
                }
            }
            setUploadLoading(-1);
        })
    }
    const handleChangeSelect = (e) => {
        setSelectedList(e || []);
        const tempIds = e?.map(key => key.value) || [];
        setIds([...tempIds]);
        filter(page, limit, filterStatus, tempIds, sort, periodCode, productTypeIds, dateRange, drugCode);
    }
    const handleChangeProductTypesSelect = (e) => {
        setSelectedProductTypes(e || []);
        const tempIds = e?.map(key => key.value) || [];
        setProductTypeIds([...tempIds]);
        filter(page, limit, filterStatus, ids, sort, periodCode, tempIds, dateRange, drugCode);
    }
    const filterDb = (arr_key, API, value) => {
        setMultiSelectLoading(true);
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            API.search(value).then((res) => {
                const new_list = [];
                res.data.data.forEach(key => { if (!ids.includes(key.id)) new_list.push({ value: key.id, label: key.name }) })
                setList([...selectedList, ...new_list])
                setMultiSelectLoading(false)
            })
        }, 1000)
        setTimer(newTimer);
    };

    const handleBulkUpdate = (status) => {
        const val = window.confirm(`Are you sure ?`);
        if (val) {
            setLoading(true);
            let temp = {};
            temp['dataID'] = [...selectedData];
            if (status == 'active') {
                temp["is_active"] = true;
                temp["deleted"] = false
            } else if (status == 'unactive') {
                temp["is_active"] = false;
                temp["deleted"] = false
            }
            API.updateSelected(temp)
                .then(res => {
                    showToast('success', res.data.message);
                    setSelectedData([]);
                    getAllList();
                }).catch(err => {
                    setLoading(false);
                    showToast('error', err.response.data.message);
                });
        }
    }
    const handleBulkDelete = () => {
        const val = window.confirm(`${TR(lang, "content.rebootAllTitle")}?`);
        if (val) {
            setLoading(true);
            let temp = {};
            temp['dataID'] = [...selectedData];
            if (filterStatus === 'active' || filterStatus === 'unactive') {
                temp["is_active"] = false;
                temp["deleted"] = true;
                API.updateSelected(temp)
                    .then(res => {
                        showToast('success', res.data.message);
                        setSelectedData([]);
                        getAllList();
                    }).catch(err => {
                        setLoading(false);
                        showToast('error', err.response.data.message);
                    });
            } else {
                API.deleteSelected(temp)
                    .then(res => {
                        showToast('success', res.data.message);
                        setSelectedData([]);
                        getAllList();
                    }).catch(err => {
                        setLoading(false);
                        showToast('error', err.response.data.message);
                    });
            }

        }
    }
    const handleReboot = () => {
        AnalyzeApi.clearRedisCache().then(res => {
            showToast('success', res.data.message);
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
    }
    const handleColumnFilter = (obj) => {
        setColumnFilter({ ...obj });
        const temp = [...columns];
        temp.forEach(el => {
            if (el.name && typeof obj[el.name] == "boolean") {
                el.hide = !obj[el.name];
            }
        });
        setColumns([...temp])
    }
    const handleUpdateLog = () => {
        setUpdateLogModal(true);
    }

    const getAllList = () => {
        filter(page, limit, filterStatus, ids, sort, periodCode, productTypeIds, dateRange, drugCode);
    }

    useEffect(() => {
        handleColumnFilter(columnActions)
        getAllList();
    }, [])
    return (
        <>
            <Route
                key={`${url}/`}
                path={`${url}/`}
                exact
            >
                <CrudTable
                    API={API}
                    isDrc={true}
                    data={data}
                    columns={columns}
                    columnFilter={columnFilter}
                    columnTitles={columnTitles}
                    title={title}
                    loading={loading}
                    totalPages={totalPages}
                    page={page}
                    limit={limit}
                    sort={sort}
                    handleSort={handleSort}
                    filterStatus={filterStatus}
                    handleFilterStatus={handleFilterStatus}
                    gotoPage={gotoPage}
                    handleLimit={handleLimit}
                    handleAdd={handleAdd}
                    handleEdit={handleEdit}
                    handleDelete={handleDelete}
                    handleStatusChange={handleStatusChange}
                    handleUpload={handleUpload}
                    selectedData={selectedData}
                    handleSelect={handleSelect}
                    handleSelectAll={handleSelectAll}
                    handleColumnFilter={handleColumnFilter}
                    multiSelectLoading={multiSelectLoading}
                    ids={ids}
                    list={list}
                    handleChangeSelect={handleChangeSelect}
                    filterDb={filterDb}
                    handleBulkDelete={handleBulkDelete}
                    handleBulkUpdate={handleBulkUpdate}
                    dataCode={periodCode}
                    drugCode={drugCode}
                    handleChangeDataCode={handleChangeUnicode}
                    handleChangeDrugCode={handleChangeDrugCode}
                    handleChangeDateRange={handleChangeDateRange}
                    dateRange={dateRange}
                    handleReboot={handleReboot}
                    handleUpdateLog={handleUpdateLog}
                    handleChangeProductTypesSelect={handleChangeProductTypesSelect}
                    productTypes={productTypes}
                    selectedProductTypes={selectedProductTypes}
                />
                <UpdateLogModal
                    updateLogModal={updateLogModal}
                    setUpdateLogModal={setUpdateLogModal}
                />
                <DeleteModal
                    del={del}
                    delId={delId}
                    delModal={delModal}
                    setDelModal={setDelModal}
                />
                <StatusModal
                    changeStatus={changeStatus}
                    id={delId}
                    statusModal={statusModal}
                    setStatusModal={setStatusModal}
                />
                <Modal show={uploadModal} onHide={setUploadModal}>
                    <Modal.Header className="c-header" closeButton>
                        <Modal.Title>Импорт excel</Modal.Title>
                    </Modal.Header>
                    <Form onSubmit={e => handleSubmitFile(e)}>
                        <Modal.Body>
                            <input
                                accept=".xlsx, .xls, .csv"
                                className="px-0 chooseFile mb-3"
                                type="file"
                                required
                            />
                            {
                                uploadLoading == 0 ? <> Uploading... </>
                                    : uploadLoading == 1 ? <> Progressing... </> : <>
                                        {
                                            uploadErrorList.map((elem, index) => {
                                                return <div className='text-danger' key={index}>
                                                    <span className='ms-3'>{index < 1000 ? index + 1 : null}.</span> {elem.message}
                                                </div>
                                            })
                                        }
                                    </>
                            }
                        </Modal.Body>
                        <Modal.Footer>
                            <Button type="submit" variant="primary">
                                Загрузить файл
                            </Button>
                        </Modal.Footer>
                    </Form>
                </Modal>
            </Route>
            <Route key={`${url}/add`} path={`${url}/add`}>
                <AddSale
                    filter={getAllList}
                    setLoading={setLoading}
                    {...props}
                />
            </Route>
            <Route
                key={`${url}/update/:id`}
                path={`${url}/update/:id`}
                render={(newProps) => <EditSale
                    filter={getAllList}
                    setLoading={setLoading}
                    {...props}
                    {...newProps}
                />}
            />
        </>
    );

}

const mapStateToProps = (state) => {
    return {
        productTypes: state.main.productTypes,
        lastUpdateDate: state.main.lastUpdateDate,
        lang: state.language.lang,
        price: state.sale.price,
        c_price: state.sale.c_price,
        sum_price: state.sale.sum_price,
    };
};

export default connect(mapStateToProps)(Sale);