export default function PaginationControls({
    meta,
    loading = false,
    onPrev,
    onNext,
}) {
    return (
        <div className="admin-pagination">
            <span>
                {meta.from || 0}-{meta.to || 0} di {meta.total || 0}
            </span>
            <div className="admin-pagination-actions">
                <button
                    type="button"
                    className="secondary"
                    disabled={(meta.current_page || 1) <= 1 || loading}
                    onClick={onPrev}
                >
                    Precedente
                </button>
                <span>
                    Pagina {meta.current_page || 1} / {meta.last_page || 1}
                </span>
                <button
                    type="button"
                    disabled={(meta.current_page || 1) >= (meta.last_page || 1) || loading}
                    onClick={onNext}
                >
                    Successiva
                </button>
            </div>
        </div>
    );
}
