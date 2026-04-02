import { useCallback, useEffect, useState } from "react";
import {
    clearAccessToken,
    fetchAuthenticatedUser,
    getAccessToken,
    redirectTo,
    safeLogout,
} from "../Shared/auth";

export default function useAdminSession({
    loginPath = "/login",
    requireAuth = true,
    redirectIfAuthenticated = false,
    authenticatedRedirectPath = "/admin",
} = {}) {
    const [token, setToken] = useState(() => getAccessToken());
    const [user, setUser] = useState(null);
    const [booting, setBooting] = useState(true);

    useEffect(() => {
        const currentToken = getAccessToken();
        setToken(currentToken);

        if (redirectIfAuthenticated && currentToken) {
            redirectTo(authenticatedRedirectPath);
            return;
        }

        if (!currentToken) {
            setToken("");
            setBooting(false);
            if (requireAuth) {
                redirectTo(loginPath);
            }
            return;
        }

        fetchAuthenticatedUser(currentToken)
            .then((authUser) => {
                setToken(currentToken);
                setUser(authUser);
                setBooting(false);
            })
            .catch(() => {
                clearAccessToken();
                setToken("");
                setUser(null);
                setBooting(false);
                if (requireAuth) {
                    redirectTo(loginPath);
                }
            });
    }, [loginPath, requireAuth, redirectIfAuthenticated, authenticatedRedirectPath]);

    const handleLogout = useCallback(async () => {
        await safeLogout({ token, redirectPath: loginPath });
    }, [token, loginPath]);

    return {
        token,
        user,
        booting,
        isAuthenticated: Boolean(token),
        handleLogout,
    };
}
