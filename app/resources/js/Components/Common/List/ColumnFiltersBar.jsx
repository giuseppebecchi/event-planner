export default function ColumnFiltersBar({
    fields,
    values,
    onFilterChange,
    minChars = 3,
}) {
    return (
        <div className="admin-column-filters">
            {fields.map((field) => (
                <label key={field.key} className="admin-column-filter-field">
                    <span>{field.label}</span>
                    <input
                        type={field.type || "text"}
                        value={values[field.key] ?? ""}
                        placeholder={field.placeholder || `Filtra ${field.label.toLowerCase()}...`}
                        onChange={(event) => onFilterChange(field.key, event.target.value)}
                    />
                </label>
            ))}
            <p className="admin-column-filters-hint">
                Filtri live: minimo {minChars} caratteri, debounce 300ms.
            </p>
        </div>
    );
}
