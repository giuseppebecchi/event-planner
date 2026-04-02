import { Link } from "@inertiajs/react";

export default function EditActionLink({ href, ariaLabel = "Modifica" }) {
    return (
        <Link className="icon-action edit" href={href} aria-label={ariaLabel}>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 20h4l10-10-4-4L4 16v4zm13.7-11.3a1 1 0 0 0 0-1.4l-1-1a1 1 0 0 0-1.4 0l-1.2 1.2 4 4 1.6-1.8z" />
            </svg>
        </Link>
    );
}
