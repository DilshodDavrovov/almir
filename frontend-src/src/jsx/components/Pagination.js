import { connect } from "react-redux";
import { canNextPage, canPreviousPage, TR } from "../../utils/helpers";

function Pagination(props) {
    const {gotoPage, page, totalPages, lang} = props;
    return <div>
        <div className="d-flex justify-content-between mt-3">
            <span>
                {TR(lang, "pagin.page")}{" "}
                <strong>
                {page} of {totalPages}
                </strong>
                {""}
            </span>
            <span className="table-index">
                {TR(lang, "pagin.go_to_page")} :{" "}
                <input
                type="number"
                className="ml-2"
                autoComplete="off"
                max={totalPages}
                value={page}
                onChange={(e) => {
                    if (e.target.value) {
                    gotoPage(Number(e.target.value));
                    }
                }}
                />
            </span>
        </div>
        <div className="text-center">
            <div className="filter-pagination  mt-3">
                <button
                className=" previous-button"
                onClick={() => gotoPage(1)}
                disabled={!canPreviousPage(page)}
                >
                {"<<"}
                </button>
                <button
                className="previous-button"
                onClick={() => gotoPage(page - 1)}
                disabled={!canPreviousPage(page)}
                >
                {TR(lang, "pagin.prev")}
                </button>
                <button
                className="next-button"
                onClick={() => gotoPage(page + 1)}
                disabled={!canNextPage(page, totalPages)}
                >
                {TR(lang, "pagin.next")}
                </button>
                <button
                className=" next-button"
                onClick={() => gotoPage(totalPages)}
                disabled={!canNextPage(page, totalPages)}
                >
                {">>"}
                </button>
            </div>
        </div>
    </div>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
    };
};
export default connect(mapStateToProps)(Pagination);
