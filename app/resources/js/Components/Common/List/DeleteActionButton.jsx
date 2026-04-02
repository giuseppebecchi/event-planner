import { useState } from "react";
import ConfirmDeleteModal from "./ConfirmDeleteModal";

export default function DeleteActionButton({
    ariaLabel = "Elimina",
    targetLabel,
    message = "Vuoi eliminare questo elemento",
    onConfirm,
}) {
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const handleConfirm = async () => {
        if (!onConfirm) return;

        setSubmitting(true);
        try {
            await onConfirm();
            setOpen(false);
        } catch (_err) {
            // Parent page handles error state/messages.
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <button
                type="button"
                className="icon-action delete"
                aria-label={ariaLabel}
                onClick={() => setOpen(true)}
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9 3h6l1 2h4v2H4V5h4l1-2zm1 6h2v9h-2V9zm4 0h2v9h-2V9zM7 9h2v9H7V9z" />
                </svg>
            </button>

            <ConfirmDeleteModal
                open={open}
                message={message}
                targetLabel={targetLabel}
                confirmLabel={submitting ? "Elimino..." : "Elimina"}
                onCancel={() => (submitting ? null : setOpen(false))}
                onConfirm={handleConfirm}
                cancelDisabled={submitting}
                confirmDisabled={submitting}
            />
        </>
    );
}
