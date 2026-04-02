export default function FormJsonField({
    label,
    field,
    form,
    setForm,
    value,
    onChange,
    error,
}) {
    const hasBinding = Boolean(field && form && setForm);

    const resolvedValue = value ?? (hasBinding ? (form[field] ?? "") : "");
    const resolvedOnChange = onChange ?? (hasBinding
        ? (nextValue) => {
            setForm((prev) => ({
                ...prev,
                [field]: nextValue,
            }));
        }
        : undefined);

    return (
        <label className="company-field company-field-full">
            <span>{label}</span>
            <textarea
                rows={8}
                value={resolvedValue}
                onChange={(event) => resolvedOnChange?.(event.target.value)}
            />
            {error ? <small className="company-json-error">{error}</small> : null}
        </label>
    );
}
