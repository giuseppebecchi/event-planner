export default function FormSelectField({
    label,
    options,
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
            <span>{label}</span>
            <select {...props} value={resolvedValue} onChange={resolvedOnChange}>
                {options.map((opt) => (
                    <option key={String(opt.value)} value={opt.value}>
                        {opt.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
