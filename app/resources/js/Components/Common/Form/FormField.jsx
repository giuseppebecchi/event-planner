export default function FormField({
    label,
    required = false,
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
        <label className="company-field">
            <span>
                {label}
                {required ? <strong>*</strong> : null}
            </span>
            <input {...props} value={resolvedValue} onChange={resolvedOnChange} />
        </label>
    );
}
