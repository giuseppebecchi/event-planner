export default function ConfirmDeleteModal({
    open,
    title = "Conferma eliminazione",
    message,
    targetLabel,
    cancelLabel = "Annulla",
    confirmLabel = "Elimina",
    cancelDisabled = false,
    confirmDisabled = false,
    onCancel,
    onConfirm,
}) {
    if (!open) return null;

    return (
        <div className="confirm-overlay" role="dialog" aria-modal="true">
            <div className="confirm-modal">
                <h3>{title}</h3>
                <p>
                    {message || "Vuoi eliminare questo elemento"} {" "}
                    <strong>{targetLabel}</strong>?
                </p>
                <div className="confirm-actions">
                    <button type="button" className="secondary" onClick={onCancel} disabled={cancelDisabled}>
                        {cancelLabel}
                    </button>
                    <button type="button" className="danger" onClick={onConfirm} disabled={confirmDisabled}>
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
