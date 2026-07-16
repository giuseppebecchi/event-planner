<x-filament-panels::page>
    <style>
        .customer-page {
            display: grid;
            gap: 1rem;
        }

        .customer-hero,
        .customer-panel {
            border: 1px solid #e8e0d6;
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 22px 48px rgba(42, 34, 26, 0.06);
        }

        .customer-hero {
            padding: clamp(1.25rem, 3vw, 2.25rem);
            background: linear-gradient(135deg, #fffaf3 0%, #f5efe7 100%);
        }

        .customer-kicker {
            margin: 0;
            color: #9b7a4a;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .customer-title {
            margin: 0.45rem 0 0;
            max-width: 52rem;
            color: #29231d;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.8rem, 4vw, 3rem);
            line-height: 1.08;
        }

        .customer-copy {
            max-width: 56rem;
            margin: 0.8rem 0 0;
            color: #6f655b;
            font-size: 1rem;
            line-height: 1.75;
        }

        .customer-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .customer-panel {
            padding: 1.15rem;
        }

        .customer-panel-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.65rem;
            height: 2.65rem;
            margin-bottom: 0.85rem;
            border-radius: 0.95rem;
            background: #f4eadc;
            color: #8a6a3c;
        }

        .customer-panel-icon svg {
            width: 1.35rem;
            height: 1.35rem;
        }

        .customer-panel h2,
        .customer-panel h3 {
            margin: 0;
            color: #2f2923;
            font-size: 1rem;
            font-weight: 800;
        }

        .customer-panel-subtitle {
            margin: 0.35rem 0 0;
            color: #8a6a3c;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .customer-panel p,
        .customer-panel li {
            color: #6f655b;
            line-height: 1.7;
        }

        .customer-panel ul {
            margin: 0.75rem 0 0;
            padding-left: 1.2rem;
        }

        .customer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.2rem;
        }

        @media (max-width: 900px) {
            .customer-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="customer-page">
        <section class="customer-hero">
            <p class="customer-kicker">Client portal</p>
            <h1 class="customer-title">Welcome, {{ auth()->user()?->name }}.</h1>
            <p class="customer-copy">
                This is your private wedding planning area. It gives you one place to follow the progress of your event, review shared materials, check important dates, and keep an eye on the planning steps that are visible to you.
            </p>
            <p class="customer-copy">
                Here you can enjoy a clearer, calmer way to manage your wedding journey and collaborate with your wedding planner, Irene. This space is designed to keep ideas, updates, and important information close at hand as your celebration takes shape.
            </p>

            <div class="customer-actions">
                <x-filament::button wire:click="continueToPortal" size="lg">
                    Enter the portal
                </x-filament::button>
            </div>
        </section>

        <div class="customer-grid">
            <section class="customer-panel">
                <div class="customer-panel-icon">
                    <x-heroicon-o-squares-2x2 />
                </div>
                <p class="customer-panel-subtitle">Your workspace</p>
                <h2>Everything in one place</h2>
                <ul>
                    <li><strong>Event dashboard:</strong> see the main planning information at a glance.</li>
                    <li><strong>Shared sections:</strong> review timelines, checklists, guests, documents, suppliers, budget details, and moodboards when they are available.</li>
                    <li><strong>Clear reference point:</strong> use the portal as the shared place for updated event information.</li>
                </ul>
            </section>

            <section class="customer-panel">
                <div class="customer-panel-icon">
                    <x-heroicon-o-map />
                </div>
                <p class="customer-panel-subtitle">How it works</p>
                <h2>Start from your event</h2>
                <p>
                    Start from your <strong>event dashboard</strong>. The navigation inside the event shows the available sections. Some areas are view-only, while others may allow you to reply, confirm, or review information depending on the planning stage.
                </p>
            </section>

            <section class="customer-panel">
                <div class="customer-panel-icon">
                    <x-heroicon-o-question-mark-circle />
                </div>
                <p class="customer-panel-subtitle">Need guidance?</p>
                <h2>Use the Help section</h2>
                <p>
                    Open the <strong>Help</strong> section from the main menu whenever you need a simple explanation of each area. It explains what every section is for and how to read the information inside it.
                </p>
            </section>
        </div>
    </div>
</x-filament-panels::page>
