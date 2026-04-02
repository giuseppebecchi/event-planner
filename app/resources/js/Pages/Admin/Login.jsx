import { Head } from "@inertiajs/react";
import { useState } from "react";
import useAdminSession from "../../Hooks/useAdminSession";
import { loginRequest, setAccessToken } from "../../Shared/auth";

export default function LoginPage() {
    useAdminSession({
        requireAuth: false,
        redirectIfAuthenticated: true,
        authenticatedRedirectPath: "/admin",
    });

    const [form, setForm] = useState({
        login: "admin@admin.come",
        password: "admin",
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError("");

        try {
            const data = await loginRequest({
                login: form.login,
                password: form.password,
                deviceName: "admin-web",
            });
            const token = data?.access_token;
            if (!token) {
                throw new Error("Token non ricevuto");
            }

            setAccessToken(token);
            window.location.replace("/admin");
        } catch (err) {
            setError(
                err?.response?.data?.errors?.login?.[0] ||
                    err?.response?.data?.message ||
                    err.message ||
                    "Login fallito.",
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Admin Login" />
            <main className="login-page">
                <form className="login-card" onSubmit={handleSubmit}>
                    <img
                        src="/images/logo.png"
                        alt="Cup Solidale"
                        className="login-logo"
                    />
                    <h1>Accesso amministratore</h1>
                    <input
                        value={form.login}
                        onChange={(e) => setForm((prev) => ({ ...prev, login: e.target.value }))}
                        placeholder="Email o username"
                        autoComplete="username"
                        required
                    />
                    <input
                        value={form.password}
                        onChange={(e) => setForm((prev) => ({ ...prev, password: e.target.value }))}
                        placeholder="Password"
                        type="password"
                        autoComplete="current-password"
                        required
                    />
                    <button type="submit" disabled={loading}>
                        {loading ? "Accesso..." : "Login"}
                    </button>
                    {error ? <p className="login-error">{error}</p> : null}
                </form>
            </main>
        </>
    );
}
