import DeleteActionButton from "./DeleteActionButton";
import EditActionLink from "./EditActionLink";

export default function RowActions({
    editHref,
    editAriaLabel,
    deleteAriaLabel,
    deleteTargetLabel,
    deleteMessage,
    onDeleteConfirm,
}) {
    return (
        <div className="admin-actions">
            <EditActionLink href={editHref} ariaLabel={editAriaLabel} />
            <DeleteActionButton
                ariaLabel={deleteAriaLabel}
                targetLabel={deleteTargetLabel}
                message={deleteMessage}
                onConfirm={onDeleteConfirm}
            />
        </div>
    );
}
