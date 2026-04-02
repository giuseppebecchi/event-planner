import axios from "axios";
import { useCallback, useEffect, useMemo, useState } from "react";
import useDebouncedValue from "../Components/Common/List/useDebouncedValue";
import { withAuth } from "../Shared/auth";
import { createMeta, normalizeMeta } from "../Shared/pagination";
import { sanitizeFiltersByMinLength, sanitizeString } from "../Shared/utils/string";
import useAdminSession from "./useAdminSession";

function defaultResolveItemId(item) {
    if (!item) return "";
    if (typeof item._id === "string") return item._id;
    if (item._id && typeof item._id === "object" && typeof item._id.$oid === "string") {
        return item._id.$oid;
    }
    if (typeof item.id === "string") return item.id;
    return String(item._id ?? item.id ?? "");
}

export default function useAdminListPage({
    endpoint,
    loginPath = "/admin/login",
    perPage = 20,
    defaultFilters = {},
    minFilterChars = 3,
    debounceMs = 300,
    resolveItemId = defaultResolveItemId,
    deletePath = (id) => `${endpoint}/${encodeURIComponent(id)}`,
    deleteErrorMessage = "Eliminazione fallita.",
    fetchErrorMessage = "Errore caricamento dati.",
} = {}) {
    const { token, user, booting, isAuthenticated, handleLogout } = useAdminSession({
        loginPath,
    });

    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(createMeta(perPage));
    const [filters, setFilters] = useState(defaultFilters);
    const [appliedFilters, setAppliedFilters] = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");

    const debouncedFilters = useDebouncedValue(filters, debounceMs);

    const loadPage = useCallback(
        async (page = 1, activeFilters = {}, activeToken = token) => {
            setLoading(true);
            setError("");

            try {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(perPage),
                });

                Object.entries(activeFilters).forEach(([key, value]) => {
                    params.set(key, value);
                });

                const response = await axios.get(`${endpoint}?${params.toString()}`, withAuth(activeToken));
                setItems(response.data?.data ?? []);
                setMeta(normalizeMeta(response.data?.meta, perPage));
            } catch (err) {
                setError(err?.response?.data?.message || fetchErrorMessage);
            } finally {
                setLoading(false);
            }
        },
        [endpoint, fetchErrorMessage, perPage, token],
    );

    useEffect(() => {
        if (!isAuthenticated || !user) return;

        const nextAppliedFilters = sanitizeFiltersByMinLength(debouncedFilters, minFilterChars);
        setAppliedFilters(nextAppliedFilters);
        loadPage(1, nextAppliedFilters, token);
    }, [debouncedFilters, isAuthenticated, loadPage, minFilterChars, token, user]);

    const onFilterChange = useCallback((key, value) => {
        setFilters((current) => ({
            ...current,
            [key]: sanitizeString(value),
        }));
    }, []);

    const deleteItem = useCallback(
        async (itemOrId) => {
            const id = typeof itemOrId === "string" ? itemOrId : resolveItemId(itemOrId);
            if (!id) return;

            try {
                await axios.delete(deletePath(id), withAuth(token));

                const nextPage = items.length === 1 && meta.current_page > 1
                    ? meta.current_page - 1
                    : meta.current_page;
                await loadPage(nextPage, appliedFilters);
            } catch (err) {
                const message = err?.response?.data?.message || deleteErrorMessage;
                setError(message);
                throw err;
            }
        },
        [appliedFilters, deleteErrorMessage, deletePath, items.length, loadPage, meta.current_page, resolveItemId, token],
    );

    const pagination = useMemo(
        () => ({
            onPrev: () => loadPage(meta.current_page - 1, appliedFilters),
            onNext: () => loadPage(meta.current_page + 1, appliedFilters),
        }),
        [appliedFilters, loadPage, meta.current_page],
    );

    return {
        token,
        user,
        booting,
        isAuthenticated,
        handleLogout,
        items,
        meta,
        filters,
        appliedFilters,
        loading,
        error,
        setError,
        loadPage,
        onFilterChange,
        resolveItemId,
        deleteItem,
        pagination,
    };
}
