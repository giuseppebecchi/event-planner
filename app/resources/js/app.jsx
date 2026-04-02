import "./bootstrap";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";
const pages = import.meta.glob("./Pages/**/*.jsx");

document.documentElement.setAttribute("data-app-js", "loaded");

const showBootError = (error) => {
    const target = document.getElementById("app");
    if (!target) return;

    const message = error instanceof Error ? `${error.name}: ${error.message}` : String(error);
    target.innerHTML = `<pre style="padding:16px;color:#b00020;white-space:pre-wrap;">Inertia bootstrap error\n${message}</pre>`;
};

window.addEventListener("error", (event) => {
    if (event?.error) showBootError(event.error);
});

window.addEventListener("unhandledrejection", (event) => {
    showBootError(event?.reason ?? "Unhandled promise rejection");
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = pages[`./Pages/${name}.jsx`];
        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        const module = await page();
        return module.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: "#40b58a",
    },
}).catch(showBootError);
