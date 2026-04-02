export default function FormPanel({ title, subtitle, children }) {
    return (
        <section className="company-panel">
            <header>
                <h3>{title}</h3>
                {subtitle ? <p>{subtitle}</p> : null}
            </header>
            <div className="company-panel-body">{children}</div>
        </section>
    );
}
