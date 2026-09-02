import React, { useState } from 'react';
import { connect } from 'react-redux';
import API from '../../../services/cruds/CounterPartyService'
import Crud from '../../components/Crud/Crud';
import { TR } from '../../../utils/helpers';

const Counterparty = ({ lang }) => {
    const [loading, setLoading] = useState(true);
    const title = "products.counterparty";
    const [data, setData] = useState([]);
    const [delId, setDelId] = useState(null);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [filterStatus, setFilterStatus] = useState("active");
    const [editData, setEditData] = useState({ name: "", is_active: true, deleted: false });
    const columns = [
        {
            Header: TR(lang, "table.id"),
            accessor: "id", serverSort: "id",
        }, {
            Header: TR(lang, "table.name"),
            accessor: "name", serverSort: "name",
        }, {
            Header: TR(lang, "products.region"),
            accessor: "region.name"
        }, {
            Header: TR(lang, "products.district"),
            accessor: "district.name"
        }
    ];
    return <>
        <Crud
            API={API}
            title={title}
            columns={columns}
            loading={loading}
            setLoading={setLoading}
            data={data}
            setData={setData}
            delId={delId}
            setDelId={setDelId}
            page={page}
            setPage={setPage}
            totalPages={totalPages}
            setTotalPages={setTotalPages}
            limit={limit}
            setLimit={setLimit}
            editData={editData}
            setEditData={setEditData}
            filterStatus={filterStatus}
            setFilterStatus={setFilterStatus}
            isCounterparty={true}
        />
    </>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(Counterparty);