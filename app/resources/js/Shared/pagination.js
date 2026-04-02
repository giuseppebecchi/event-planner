export function createMeta(perPage = 20) {
    return {
        current_page: 1,
        per_page: perPage,
        total: 0,
        last_page: 1,
        from: 0,
        to: 0,
        next_page_url: null,
    };
}

export function normalizeMeta(meta, perPage = 20) {
    return {
        ...createMeta(perPage),
        ...(meta || {}),
    };
}
