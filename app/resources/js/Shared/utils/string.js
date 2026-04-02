export function sanitizeString(value) {
    return String(value ?? "").replace(/\s+/g, " ").trim();
}

export function minCharsOrEmpty(value, minChars = 3) {
    const normalized = sanitizeString(value);
    return normalized.length >= minChars ? normalized : "";
}

export function sanitizeFiltersByMinLength(filters, minChars = 3) {
    const result = {};

    Object.entries(filters || {}).forEach(([key, value]) => {
        const normalized = minCharsOrEmpty(value, minChars);
        if (normalized) {
            result[key] = normalized;
        }
    });

    return result;
}
