export default function FormTextAreaField({
    label,
    rows = 4,
    field,
    form,
    setForm,
    value,
    onChange,
    ...props
}) {
    const hasBinding = Boolean(field && form && setForm);

    const resolvedValue = value ?? (hasBinding ? (form[field] ?? "") : "");
    const resolvedOnChange = onChange ?? (hasBinding
        ? (event) => {
            setForm((prev) => ({
                ...prev,
                [field]: event.target.value,
            }));
        }
        : undefined);

    return (
        <label className="company-field company-field-full">
            <span>{label}</span>
            <textarea rows={rows} {...props} value={resolvedValue} onChange={resolvedOnChange} />
        </label>
    );
}
