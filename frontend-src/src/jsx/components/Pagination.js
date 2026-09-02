import { connect } from "react-redux";
import { canNextPage, canPreviousPage, TR } from "../../utils/helpers";

function Pagination(props) {
    const { gotoPage, page, totalPages, lang } = props;
    const prevOk = canPreviousPage(page);
    const nextOk = canNextPage(page, totalPages);
    return (
        <div className="pg">
            <div className="pg-info">
                {TR(lang, "pagin.page")} <b>{page}</b> / {totalPages || 1}
            </div>
            <div className="pg-ctrl">
                <label className="pg-goto">
                    <span>{TR(lang, "pagin.go_to_page")}:</span>
                    <input
                        type="number"
                        min={1}
                        max={totalPages}
                        autoComplete="off"
                        value={page}
                        onChange={(e) => {
                            if (e.target.value) gotoPage(Number(e.target.value));
                        }}
                    />
                </label>
                <div className="pg-btns">
                    <button type="button" className="pg-btn" onClick={() => gotoPage(1)} disabled={!prevOk} title="1">
                        <i className="fas fa-angle-double-left" aria-hidden="true" />
                    </button>
                    <button type="button" className="pg-btn" onClick={() => gotoPage(page - 1)} disabled={!prevOk}>
                        <i className="fas fa-angle-left" aria-hidden="true" />
                        <span>{TR(lang, "pagin.prev")}</span>
                    </button>
                    <button type="button" className="pg-btn" onClick={() => gotoPage(page + 1)} disabled={!nextOk}>
                        <span>{TR(lang, "pagin.next")}</span>
                        <i className="fas fa-angle-right" aria-hidden="true" />
                    </button>
                    <button type="button" className="pg-btn" onClick={() => gotoPage(totalPages)} disabled={!nextOk} title={String(totalPages)}>
                        <i className="fas fa-angle-double-right" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    );
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
    };
};
export default connect(mapStateToProps)(Pagination);
