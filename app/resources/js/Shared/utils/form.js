export function formatDateTimeLocal(value) {
    if (!value) return "";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";

    const pad = (n) => String(n).padStart(2, "0");
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function prettyJson(value, type) {
    if (value === undefined || value === null || value === "") {
        return type === "array" ? "[]" : "{}";
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch (_err) {
        return type === "array" ? "[]" : "{}";
    }
}
