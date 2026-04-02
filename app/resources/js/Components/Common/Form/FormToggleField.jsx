export default function FormToggleField({
    label,
    field,
    form,
    setForm,
    checked,
    onChange,
}) {
    const hasBinding = Boolean(field && form && setForm);

    const resolvedChecked = checked ?? (hasBinding ? Boolean(form[field]) : false);
    const resolvedOnChange = onChange ?? (hasBinding
        ? (nextChecked) => {
            setForm((prev) => ({
                ...prev,
                [field]: nextChecked,
            }));
        }
        : undefined);

    return (
        <label className="company-toggle">
            <input
                type="checkbox"
                checked={resolvedChecked}
                onChange={(event) => resolvedOnChange?.(event.target.checked)}
            />
            <span>{label}</span>
        </label>
    );
}
