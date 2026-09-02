import React, { useState, useEffect } from 'react';
import { Route, useHistory } from 'react-router-dom';
import CrudTable from '../../../components/CrudTable/CrudTable';
import API from '../../../../services/cruds/UserService';
import DeleteModal from '../../../components/Modals/DeleteModal';
import EditUser from './EditUser';
import { connect } from 'react-redux';
import { TR } from '../../../../utils/helpers';
import ChangePasswordModal from '../../../components/Modals/ChangePasswordModal';
import { showToast } from '../../../../utils';
import StatusModal from '../../../components/Modals/StatusModal';
import ChangeAccessModal from "../../../components/Modals/ChangeAcessModal";


function User(props) {
    const title = "cruds.user";
    const history = useHistory();
    const { lang } = props;
    const { url } = props.match;
    const [loading, setLoading] = useState(true);
    const [sort, setSort] = useState({ key: null, value: true });
    const [data, setData] = useState([]);
    const [filterData, setFilterData] = useState({
        "company_name": "",
        "company_inn": "",
        "email": "",
        "phone_number": "",
    });
    const [id, setId] = useState(null);
    const [timer, setTimer] = useState(null)
    const [user, setUser] = useState({});
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [filterStatus, setFilterStatus] = useState("active");
    const [delModal, setDelModal] = useState(false);
    const [delId, setDelId] = useState(null);
    const [statusModal, setStatusModal] = useState(false);
    const [changePassModal, setChangePassModal] = useState(false);
    const [changeAccessModal, setChangeAccessModal] = useState(false);
    const columns = [
        {
            Header: TR(lang, "reg.name"),
            serverSort: "first_name",
            accessor: 'first_name',
        }, {
            Header: TR(lang, "reg.secName"),
            serverSort: "last_name",
            accessor: 'last_name',
        }, {
            Header: TR(lang, "reg.compName"),
            serverSort: "company_name",
            accessor: "company_name",
        }, {
            Header: TR(lang, "content.role"),
            accessor: 'user_role',
            serverSort: "user_role",
            Cell: ({ value }) => {
                if (value == 1) return "Admin";
                if (value == 2) return "Employe";
                if (value == 3) return "User";
                if (value == 4) return "Guest";
                if (value == 5) return "Demo";
                return null;
            }
        }, {
            Header: TR(lang, "reg.phone"),
            serverSort: "phone_number",
            accessor: 'phone_number',
        }, {
            Header: TR(lang, "cruds.OTMCreated"),
            serverSort: "otm_created_at",
            accessor: `otm_created_at`,
        }, {
            Header: TR(lang, "cruds.UMExp"),
            serverSort: "um_expired_at",
            accessor: `um_expired_at`,
        }
    ];

    const handleAdd = () => {
        history.push('/user/add');
    };
    const handleEdit = (item) => {
        history.push(`/user/update/${item.id}`)
    };
    const handleChangePassword = (item) => {
        setChangePassModal(true);
        setId({ id: item.id })
    }
    const handleChangeAccess = (item) => {
        setChangeAccessModal(true);
        setUser(item)
    }
    const handleDelete = (item) => {
        setDelModal(true);
        setId({ id: item.id, deleted: item.deleted })
    };
    const handleStatusChange = (item) => {
        setStatusModal(true);
        setDelId({ id: item.id });
    };
    const handleLimit = (limit) => {
        setLimit(limit);
        filter(page, limit, filterStatus, sort, filterData)
    };
    const handleFilterStatus = (filterStatus) => {
        setFilterStatus(filterStatus);
        filter(page, limit, filterStatus, sort, filterData)
    }
    const gotoPage = (page) => {
        setPage(page)
        filter(page, limit, filterStatus, sort, filterData)
    };
    const handleSort = (key, value) => {
        setSort({ key, value });
        filter(page, limit, filterStatus, { key, value }, filterData);
    }


    const handleChangeUserFilter = (key, e) => {
        const temp = {
            ...filterData
        }
        temp[key] = e.target.value;

        setFilterData(temp)
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            filter(page, limit, filterStatus, sort, temp);
        }, 1000)
        setTimer(newTimer);
    }

    const filter = (page, limit, filterStatus, sort, filter_data) => {
        setLoading(true);
        API.getList(
            page,
            limit,
            filterStatus == 'active',
            filterStatus == 'deleted',
            filterStatus == 'blocked',
            sort,
            filter_data
        ).then(resp => {
            setData(resp.data.data);
            setTotalPages(resp.data.pagination.total_pages);
            setLoading(false)
        })
    };
    const getAllList = () => {
        filter(page, limit, filterStatus, sort, filterData)
    };


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

    useEffect(() => {
        getAllList()
    }, [])
    return (
        <>
            <Route key={`${url}/`} path={`${url}/`} exact>
                <CrudTable
                    isUser={true}
                    data={data}
                    columns={columns}
                    title={title}
                    loading={loading}
                    totalPages={totalPages}
                    page={page}
                    limit={limit}
                    sort={sort}
                    handleSort={handleSort}
                    filterStatus={filterStatus}
                    handleFilterStatus={handleFilterStatus}
                    handleStatusChange={handleStatusChange}
                    gotoPage={gotoPage}
                    handleLimit={handleLimit}
                    handleAdd={handleAdd}
                    handleEdit={handleEdit}
                    handleChangePassword={handleChangePassword}
                    handleChangeAccess={handleChangeAccess}
                    handleDelete={handleDelete}
                    handleChangeUserFilter={handleChangeUserFilter}
                    userFilterData={filterData}
                />
                <StatusModal
                    changeStatus={changeStatus}
                    id={delId}
                    statusModal={statusModal}
                    setStatusModal={setStatusModal}
                />
                <DeleteModal
                    del={del}
                    delId={id}
                    delModal={delModal}
                    setDelModal={setDelModal}
                />
            </Route>
            <Route
                key={`${url}/update/:id`}
                path={`${url}/update/:id`}
                render={(newsProps) => <EditUser
                    filter={getAllList}
                    setLoading={setLoading}
                    {...props}
                    {...newsProps}
                />}
            >
            </Route>
            {
                user && user.access &&
                <ChangeAccessModal
                    getAllList={getAllList}
                    user={user}
                    show={changeAccessModal}
                    setShow={setChangeAccessModal}
                    userId={id}
                />
            }
            <ChangePasswordModal
                isChangeOther={true}
                show={changePassModal}
                setShow={setChangePassModal}
                userId={id}
            />
        </>
    )
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(User);