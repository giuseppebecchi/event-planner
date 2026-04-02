import axios from "axios";

export const ADMIN_TOKEN_KEY = "admin_access_token";

export function getAccessToken() {
    return localStorage.getItem(ADMIN_TOKEN_KEY) ?? "";
}

export function setAccessToken(token) {
    localStorage.setItem(ADMIN_TOKEN_KEY, token);
}

export function clearAccessToken() {
    localStorage.removeItem(ADMIN_TOKEN_KEY);
}

export function withAuth(token = getAccessToken()) {
    return {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    };
}

export function redirectTo(path = "/login") {
    window.location.replace(path);
}

export async function loginRequest({ login, password, deviceName = "admin-web" }) {
    const response = await axios.post("/api/auth/login", {
        login,
        password,
        device_name: deviceName,
    });

    return response.data ?? {};
}

export async function fetchAuthenticatedUser(token = getAccessToken()) {
    const response = await axios.get("/api/auth/me", withAuth(token));
    return response.data?.user ?? null;
}

export async function logoutRequest(token = getAccessToken()) {
    await axios.post("/api/auth/logout", {}, withAuth(token));
}

export async function safeLogout({
    token = getAccessToken(),
    redirectPath = "/login",
} = {}) {
    try {
        await logoutRequest(token);
    } catch (_err) {
        // ignore network/logout errors
    }

    clearAccessToken();
    redirectTo(redirectPath);
}
