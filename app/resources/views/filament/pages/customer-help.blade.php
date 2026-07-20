<x-filament-panels::page>
    <style>
        .customer-help {
            display: grid;
            gap: 1rem;
        }

        .customer-help-hero,
        .customer-help-section {
            border: 1px solid #e8e0d6;
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 42px rgba(42, 34, 26, 0.05);
        }

        .customer-help-hero {
            padding: 1.5rem;
            background: linear-gradient(135deg, #fffaf3 0%, #f6f0e8 100%);
        }

        .customer-help-hero h1 {
            margin: 0;
            color: #29231d;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.6rem, 3vw, 2.4rem);
        }

        .customer-help-hero p,
        .customer-help-section p,
        .customer-help-section li {
            color: #6f655b;
            line-height: 1.75;
        }

        .customer-help-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .customer-help-section {
            padding: 1.15rem;
        }

        .customer-help-section h2 {
            margin: 0;
            color: #2f2923;
            font-size: 1rem;
            font-weight: 800;
        }

        .customer-help-section ul {
            margin: 0.65rem 0 0;
            padding-left: 1.2rem;
        }

        .customer-help-update-note {
            display: block;
            margin-top: 0.55rem;
            color: #b42318;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .customer-help-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="customer-help">
        <section class="customer-help-hero">
            <h1>Help</h1>
            <p>
                This guide explains the sections available in your event workspace. The portal is designed to give you a clear view of your wedding planning progress without needing any technical knowledge.
            </p>
        </section>

        <div class="customer-help-grid">
            <section class="customer-help-section">
                <h2>Dashboard</h2>
                <p>
                    The event dashboard is the starting point for your wedding. It summarizes the date, location, guest count, planning progress, budget overview, supplier status, and important next steps.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Checklist</h2>
                <p>
                    The checklist shows planning tasks and their status. Some items may be informational, while others may need your attention. Use it to understand what has been completed and what is still in progress.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Calendar</h2>
                <p>
                    The calendar collects important dates such as scheduled events, payments, deadlines, and planning milestones. It helps you understand what is coming next.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Timeline</h2>
                <p>
                    The timeline shows the flow of the wedding day or wedding days. It may include moments such as guest arrival, ceremony, aperitivo, dinner, speeches, cake cutting, and party.
                    <span class="customer-help-update-note">We will keep it updated as the wedding planning progresses.</span>
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Recap</h2>
                <p>
                    The recap is a readable summary of the most important planning information. It is useful when you want an overview without opening each section separately.
                    <span class="customer-help-update-note">It will be updated step-by-step as we move forward with the planning.</span>
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Layouts</h2>
                <p>
                    Layouts contain floor plans, seating plans, or visual arrangements when available. This section helps you understand how spaces and tables are organized.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Website</h2>
                <p>
                    If your event website is active, this area contains configuration and preview information. It may be used for guest-facing details and RSVP flows.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Moodboard</h2>
                <p>
                    Moodboards collect visual inspiration such as images, Pinterest boards, and PDF moodboards. They help define the style, atmosphere, colors, and creative direction of the event.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Guests</h2>
                <p>
                    The guest section contains guest information and RSVP-related details when enabled. It helps keep attendance and guest responses organized.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Budget</h2>
                <p>
                    The budget section shows the planning categories shared with you. You may see estimated, compared, or confirmed amounts depending on the stage of the planning process.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Suppliers</h2>
                <p>
                    Suppliers include venues and service providers connected to your wedding. You may see confirmed suppliers, selected proposals, documents, payment information, or public notes depending on what has been shared.
                </p>
            </section>

            <section class="customer-help-section">
                <h2>Documents</h2>
                <p>
                    Documents are files shared for your event, such as contracts, proposals, quotes, receipts, or planning documents. Use this section to find important files in one place.
                </p>
            </section>
        </div>
    </div>
</x-filament-panels::page>
