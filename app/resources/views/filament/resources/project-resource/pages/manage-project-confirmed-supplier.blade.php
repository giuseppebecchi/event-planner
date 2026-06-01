<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summary = $this->getSummary();
        $dashboardCards = $this->getDashboardCards();
        $communications = $this->getCommunications();
        $quoteDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_QUOTE);
        $contractDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_CONTRACT);
        $signedContractDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_SIGNED_CONTRACT);
        $invoiceDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_INVOICE);
        $paymentReceiptDocuments = $this->getDocumentsByType(\App\Models\ProjectDocument::TYPE_PAYMENT_RECEIPT);
        $otherDocuments = $this->getOtherDocuments();
        $payments = $this->getPayments();
        $images = $this->getImages();
        $checklistSummary = $this->getChecklistSummary();
        $checklistSections = $this->getChecklistSections();
        $supplierOptions = $this->getSupplierOptions();
        $commissionSummary = $this->getCommissionSummary();
        $isCustomer = auth()->user()?->isCustomer();
    @endphp

    <style>
        .wm-supplier-manage-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        [x-cloak] {
            display: none !important;
        }

        .wm-event-card {
            border: 1px solid var(--cup-border-soft, #e8e3dc);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 20px 42px rgba(45, 42, 38, 0.06);
        }

        .wm-event-top {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            align-items: start;
            padding: 0.9rem 1rem 1rem;
        }

        .wm-event-top-head {
            width: 100%;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.9rem 1rem;
            align-items: center;
        }

        .wm-event-top-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: clamp(1.2rem, 1.8vw, 1.65rem);
            line-height: 1.08;
            color: #2d2a26;
        }

        .wm-event-top-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem 0.95rem;
            margin-top: 0.4rem;
            color: #746d66;
            font-size: 0.86rem;
            line-height: 1.5;
        }

        .wm-event-top-meta span {
            position: relative;
        }

        .wm-event-top-meta span:not(:last-child)::after {
            content: "•";
            margin-left: 0.95rem;
            color: #c9a96a;
        }

        .wm-event-top-side {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .wm-event-summary-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 6rem;
            padding: 0.62rem 0.78rem;
            border-radius: 1rem;
            border: 1px solid rgba(201, 169, 106, 0.22);
            background: rgba(255, 255, 255, 0.85);
            color: #5f5953;
        }

        .wm-event-summary-chip-label {
            margin: 0;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #9a8f82;
        }

        .wm-event-summary-chip-value {
            margin: 0.16rem 0 0;
            font-size: 0.98rem;
            font-weight: 700;
            color: #2d2a26;
        }

        .wm-event-countdown {
            min-width: 11.5rem;
            padding: 0.62rem 0.82rem;
            border-radius: 1rem;
            background: linear-gradient(160deg, rgba(46, 74, 98, 0.96), rgba(36, 60, 81, 0.98));
            color: #f7f3ed;
        }

        .wm-event-countdown-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .wm-event-countdown-label {
            margin: 0;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.64);
        }

        .wm-event-countdown-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            color: rgba(255, 255, 255, 0.86);
            cursor: pointer;
        }

        .wm-event-countdown-value {
            margin: 0.18rem 0 0;
            color: #fff;
            font-size: 1.08rem;
            font-weight: 700;
        }

        .wm-event-countdown-meta {
            margin: 0.1rem 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.8rem;
        }

        .wm-event-workspace {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            overflow-x: auto;
            width: 100%;
            padding: 0.28rem;
            border-radius: 1.2rem;
            background: rgba(247, 243, 237, 0.96);
            scrollbar-width: none;
            border: 1px solid #ece5dd;
        }

        .wm-event-workspace::-webkit-scrollbar {
            display: none;
        }

        .wm-event-workspace-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            min-height: 2.45rem;
            padding: 0 0.88rem;
            border-radius: 999px;
            color: #746d66;
            font-size: 0.69rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: nowrap;
            text-decoration: none;
        }

        .wm-event-workspace-link:hover {
            background: rgba(122, 143, 123, 0.10);
            color: #617563;
        }

        .wm-event-workspace-link.is-active {
            background: rgba(122, 143, 123, 0.14);
            color: #2d7a39;
        }

        .wm-event-top-date-tools {
            width: 100%;
        }

        .wm-event-date-editor {
            display: grid;
            gap: 0.85rem;
            width: 100%;
            max-width: 38rem;
            padding: 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-event-date-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #4d473f;
            font-weight: 600;
        }

        .wm-event-date-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .wm-event-date-grid.is-single {
            grid-template-columns: minmax(0, 1fr);
            max-width: 16rem;
        }

        .wm-event-date-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-event-date-input {
            width: 100%;
            min-height: 2.9rem;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0 0.95rem;
            color: #2d2a26;
        }

        .wm-event-date-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .wm-card {
            border: 1px solid var(--cup-border-soft, #e8e3dc);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 20px 42px rgba(45, 42, 38, 0.06);
        }

        .wm-panel {
            padding: 1.2rem 1.25rem;
        }

        .wm-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-link {
            color: #2e4a62;
            font-weight: 700;
            text-decoration: none;
        }

        .wm-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            color: #2d2a26;
            font-size: clamp(1.3rem, 1.9vw, 1.85rem);
        }

        .wm-copy {
            margin: 0.35rem 0 0;
            color: #746d66;
            line-height: 1.65;
        }

        .wm-quote-badge {
            min-width: 15rem;
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(240, 248, 242, 0.98), rgba(250, 252, 250, 0.98));
            border: 1px solid rgba(83, 168, 106, 0.18);
        }

        .wm-quote-badge-label {
            margin: 0;
            color: #6d8a73;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-quote-badge-value {
            margin: 0.45rem 0 0;
            color: #2d2a26;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .wm-quote-badge-meta {
            margin: 0.45rem 0 0;
            color: #746d66;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .wm-quote-badge-meta.is-positive {
            color: #2d7a39;
            font-weight: 700;
        }

        .wm-quote-badge-meta.is-negative {
            color: #b54c3d;
            font-weight: 700;
        }

        .wm-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
            gap: 1rem;
        }

        .wm-dashboard-card {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.55rem 0.85rem;
            width: 100%;
            padding: 1.05rem 1.1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(251, 248, 244, 0.95), rgba(255, 255, 255, 0.98));
            border: 1px solid #ece5dd;
            text-align: left;
            cursor: pointer;
            transition: border-color 140ms ease, box-shadow 140ms ease, transform 140ms ease, background 140ms ease;
        }

        .wm-dashboard-card:hover,
        .wm-dashboard-card:focus-visible,
        .wm-dashboard-card.is-active {
            border-color: rgba(201, 169, 106, 0.42);
            background: linear-gradient(180deg, rgba(247, 243, 237, 0.98), rgba(255, 255, 255, 0.98));
        }

        .wm-dashboard-card:hover,
        .wm-dashboard-card:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(45, 42, 38, 0.10);
            outline: none;
        }

        .wm-dashboard-card.is-active {
            border-color: rgba(83, 168, 106, 0.36);
            box-shadow: 0 18px 34px rgba(83, 168, 106, 0.14);
        }

        .wm-dashboard-card.is-active::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.32rem;
            border-radius: 1rem 0 0 1rem;
            background: linear-gradient(180deg, #53a86a 0%, #2d7a39 100%);
        }

        .wm-dashboard-card.is-active .wm-dashboard-label {
            color: #4f7a57;
        }

        .wm-dashboard-card.is-active .wm-dashboard-value {
            color: #2d7a39;
        }

        .wm-dashboard-card:hover .wm-dashboard-action,
        .wm-dashboard-card:focus-visible .wm-dashboard-action,
        .wm-dashboard-card.is-active .wm-dashboard-action {
            background: #2e4a62;
            border-color: #2e4a62;
            color: #fff;
        }

        .wm-dashboard-card:hover .wm-dashboard-action svg,
        .wm-dashboard-card:focus-visible .wm-dashboard-action svg {
            transform: translateX(2px);
        }

        .wm-dashboard-copy {
            display: grid;
            gap: 0.55rem;
            min-width: 0;
        }

        .wm-dashboard-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wm-dashboard-value {
            margin: 0;
            color: #2d2a26;
            font-family: 'Cinzel', serif;
            font-size: 1.45rem;
            line-height: 1.05;
        }

        .wm-dashboard-meta {
            margin: 0;
            color: #746d66;
            line-height: 1.55;
        }

        .wm-dashboard-footer {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            min-height: 1.55rem;
            margin-top: 0.1rem;
            padding: 0 0.55rem;
            border-radius: 999px;
            background: rgba(181, 76, 61, 0.08);
            color: #9f4336;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .wm-dashboard-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: start;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            border: 1px solid rgba(46, 74, 98, 0.20);
            background: rgba(46, 74, 98, 0.07);
            color: #2e4a62;
            transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease;
        }

        .wm-dashboard-action svg {
            width: 1rem;
            height: 1rem;
            transition: transform 140ms ease;
        }

        .wm-section {
            scroll-margin-top: 7rem;
        }

        .wm-section-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .wm-section-title {
            margin: 0;
            font-family: 'Cinzel', serif;
            color: #2d2a26;
            font-size: 1.08rem;
        }

        .wm-section-subtitle {
            margin: 0.35rem 0 0;
            color: #746d66;
            line-height: 1.65;
        }

        .wm-two-col {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(22rem, 0.9fr);
            gap: 1rem;
        }

        .wm-three-col {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-form,
        .wm-list {
            display: grid;
            gap: 0.85rem;
        }

        .wm-inline-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .wm-label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5e5852;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-field,
        .wm-select,
        .wm-textarea {
            width: 100%;
            border-radius: 0.95rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            padding: 0.8rem 0.95rem;
            color: #2d2a26;
        }

        .wm-textarea {
            min-height: 7rem;
            resize: vertical;
        }

        .wm-item {
            padding: 0.95rem 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-item-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 1rem;
        }

        .wm-item-title {
            margin: 0;
            color: #2d2a26;
            font-weight: 700;
        }

        .wm-item-meta {
            margin: 0.2rem 0 0;
            color: #7b746e;
            font-size: 0.85rem;
        }

        .wm-item-copy {
            margin: 0.65rem 0 0;
            color: #605953;
            line-height: 1.65;
            white-space: pre-line;
        }

        .wm-badge {
            display: inline-flex;
            align-items: center;
            min-height: 1.8rem;
            padding: 0 0.75rem;
            border-radius: 999px;
            background: rgba(46, 74, 98, 0.08);
            color: #2e4a62;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .wm-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 0.85rem;
        }

        .wm-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .wm-radio-option {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            border: 1px solid #ece5dd;
            background: #fbf8f4;
            color: #4d473f;
            font-weight: 600;
        }

        .wm-radio-option input {
            margin: 0;
        }

        .wm-inline-form {
            margin-top: 0.9rem;
            padding-top: 0.9rem;
            border-top: 1px dashed #ddd2c5;
        }

        .wm-empty {
            padding: 1rem;
            border-radius: 1rem;
            background: #fbf8f4;
            border: 1px dashed #ddd2c5;
            color: #7b746e;
        }

        .wm-doc-groups {
            display: grid;
            gap: 1rem;
        }

        .wm-doc-group {
            display: grid;
            gap: 0.85rem;
        }

        .wm-doc-group-title {
            margin: 0;
            color: #2d2a26;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .wm-gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .wm-gallery-card {
            overflow: hidden;
            padding: 0.7rem;
            border-radius: 1.15rem;
            background: #fbf8f4;
            border: 1px solid #ece5dd;
        }

        .wm-gallery-card img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            border-radius: 0.95rem;
        }

        .wm-gallery-copy {
            padding: 0.8rem 0.2rem 0.1rem;
        }

        .wm-checklist-grid {
            display: grid;
            gap: 0.85rem;
        }

        .wm-checklist-item {
            padding: 1rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(251, 248, 244, 0.96), rgba(255, 255, 255, 0.98));
            border: 1px solid #ece5dd;
        }

        .wm-checklist-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .wm-checklist-stat {
            padding: 1.15rem 1.2rem;
        }

        .wm-checklist-stat-label {
            margin: 0;
            color: #8b847d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .wm-checklist-stat-value {
            margin: 0.55rem 0 0;
            color: #2d2a26;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .wm-checklist-stat-meta {
            margin: 0.55rem 0 0;
            color: #746d66;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .wm-checklist-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.7rem;
        }

        .wm-checklist-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.8rem 1rem;
            border-radius: 999px;
            border: 1px solid #e7dfd5;
            background: rgba(255, 255, 255, 0.92);
            color: #5f5953;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .wm-checklist-board {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
            align-items: start;
        }

        .wm-checklist-section {
            padding: 1.25rem 1.35rem;
        }

        .wm-checklist-section-head {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.15rem;
        }

        .wm-checklist-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4.2rem;
            height: 4.2rem;
            border-radius: 999px;
            border: 3px solid #d7d1ca;
            background: #f8f5f1;
            color: #bbb4ad;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.06em;
        }

        .wm-checklist-section-title {
            margin: 0;
            color: #111;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wm-checklist-section-subtitle {
            margin: 0.25rem 0 0;
            color: #6f6963;
            font-size: 0.95rem;
            font-style: italic;
        }

        .wm-checklist-items {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .wm-checklist-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.9rem;
            align-items: start;
            padding: 0.35rem 0;
            position: relative;
        }

        .wm-checklist-row.is-completed {
            opacity: 0.54;
        }

        .wm-checklist-row.is-expanded {
            z-index: 45;
        }

        .wm-checklist-toggle {
            margin-top: 0.45rem;
            width: 2rem;
            height: 2rem;
            border-radius: 0.45rem;
            border: 2px solid #d4cec5;
            background: #fffdf9;
            accent-color: #c8bf7a;
        }

        .wm-checklist-main {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 0;
        }

        .wm-checklist-summary-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            min-height: 2.6rem;
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
        }

        .wm-checklist-summary-copy,
        .wm-checklist-editor,
        .wm-checklist-schedule {
            display: grid;
            gap: 0.5rem;
            min-width: 0;
        }

        .wm-checklist-summary-title {
            color: #4f4943;
            font-size: 0.98rem;
            line-height: 1.45;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-checklist-summary-details {
            color: #9a9289;
            font-size: 0.82rem;
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wm-checklist-title-input,
        .wm-checklist-details-input,
        .wm-checklist-response-input {
            width: 100%;
            border: 0;
            border-bottom: 1px solid transparent;
            background: transparent;
            color: #4f4943;
            padding: 0.1rem 0;
            outline: none;
        }

        .wm-checklist-details-input,
        .wm-checklist-response-input {
            min-height: 3.4rem;
            resize: vertical;
            font-size: 0.9rem;
            line-height: 1.45;
            color: #877e75;
        }

        .wm-checklist-response-box {
            display: grid;
            gap: 0.35rem;
            padding: 0.65rem 0.75rem;
            border: 1px solid #ece5dd;
            border-radius: 0.85rem;
            background: #fffdf9;
        }

        .wm-checklist-response-label,
        .wm-checklist-fill-toggle,
        .wm-checklist-supplier-label {
            color: #7a7168;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .wm-checklist-fill-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
        }

        .wm-checklist-supplier-field {
            display: grid;
            gap: 0.35rem;
            max-width: 24rem;
        }

        .wm-checklist-schedule-toggle,
        .wm-checklist-meta,
        .wm-checklist-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .wm-checklist-schedule-chip {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            padding: 0 0.85rem;
            border-radius: 999px;
            border: 1px solid #e2d8ca;
            background: #fbf8f4;
            color: #6c645d;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-checklist-schedule-chip.is-active {
            border-color: #c9a96a;
            background: #fffaf2;
            color: #8f6d29;
        }

        .wm-checklist-schedule-grid {
            display: grid;
            grid-template-columns: 6rem 9rem;
            gap: 0.65rem;
            align-items: center;
        }

        .wm-checklist-schedule-input,
        .wm-checklist-schedule-select,
        .wm-checklist-supplier-select {
            width: 100%;
            min-height: 2.55rem;
            border: 1px solid #ddd2c5;
            border-radius: 0.85rem;
            background: #fff;
            padding: 0 0.85rem;
            color: #4f4943;
        }

        .wm-checklist-schedule-date {
            width: min(14rem, 100%);
        }

        .wm-checklist-pill {
            display: inline-flex;
            align-items: center;
            min-height: 1.7rem;
            padding: 0 0.65rem;
            border-radius: 999px;
            background: #f6f1e8;
            color: #665f57;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .wm-checklist-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.55rem;
            min-width: 8.5rem;
            padding-top: 0.4rem;
        }

        .wm-checklist-time {
            color: #6f6963;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .wm-checklist-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: #f6f1e8;
            color: #a16c63;
            cursor: pointer;
        }

        .wm-checklist-divider {
            height: 1px;
            background: #ece4da;
            margin: 0.1rem 0;
        }

        .wm-checklist-empty {
            color: #a29a92;
            font-size: 1rem;
            font-style: italic;
            padding: 0.3rem 0;
        }

        .wm-checklist-add-row {
            margin-bottom: 0.95rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #ece4da;
            display: flex;
            justify-content: flex-start;
        }

        .wm-checklist-add-button {
            border: 0;
            background: transparent;
            color: #b38b43;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
        }

        .wm-checklist-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 40;
            background: rgba(39, 32, 24, 0.18);
        }

        .wm-checklist-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 50;
            width: min(31rem, calc(100vw - 2rem));
            transform: translate(-50%, -50%);
            border: 2px solid #d93025;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 24px 60px rgba(24, 18, 14, 0.18);
            padding: 1.65rem;
        }

        .wm-checklist-modal-copy {
            margin: 0;
            color: #d93025;
            font-size: 1.1rem;
            line-height: 1.55;
            text-align: center;
        }

        .wm-checklist-modal-actions {
            display: flex;
            justify-content: center;
            gap: 0.9rem;
            margin-top: 1.35rem;
        }

        .wm-checklist-modal-button {
            min-width: 8.6rem;
            min-height: 3rem;
            border: 1px solid #ddd2c5;
            background: #fff;
            color: #6f6963;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .wm-checklist-modal-button.is-danger {
            border-color: #d93025;
            color: #d93025;
        }

        .wm-commission-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .wm-commission-payment-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
            gap: 0.75rem;
            align-items: end;
        }

        .wm-commission-payment-grid .is-wide {
            grid-column: span 2;
        }

        .wm-commission-payment-grid .is-row-break {
            grid-column-start: 1;
        }

        .wm-commission-delete-button {
            width: 2.65rem;
            height: 2.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(217, 48, 37, .24);
            border-radius: .75rem;
            background: rgba(217, 48, 37, .08);
            color: #d93025;
            cursor: pointer;
        }

        .wm-commission-delete-button svg {
            width: 1rem;
            height: 1rem;
        }

        .wm-field[readonly] {
            background: #f5f0e9;
            color: #6a625a;
        }

        @media (max-width: 1100px) {
            .wm-top-kpis,
            .wm-dashboard-grid,
            .wm-two-col,
            .wm-three-col,
            .wm-inline-grid,
            .wm-checklist-summary,
            .wm-checklist-board,
            .wm-commission-summary,
            .wm-commission-payment-grid,
            .wm-gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="wm-supplier-manage-page">
        @include('filament.resources.project-resource.partials.workspace-header', [
            'record' => $record,
            'activeSection' => 'suppliers',
        ])

        <section class="wm-card wm-panel">
            <div class="wm-head">
                <div>
                    <a href="{{ \App\Filament\Resources\ProjectResource::getUrl('suppliers', ['record' => $record]) }}" class="wm-link">← Back to suppliers</a>
                    <h2 class="wm-title" style="margin-top:.45rem;">{{ $summary['supplier'] }}</h2>
                    <p class="wm-copy">{{ $summary['category'] }} · confirmed supplier workspace for this project.</p>
                </div>
                <div class="wm-quote-badge">
                    <p class="wm-quote-badge-label">Confirmed quote</p>
                    <p class="wm-quote-badge-value">{{ $summary['confirmed_amount'] !== null ? 'EUR ' . number_format($summary['confirmed_amount'], 2, ',', '.') : '—' }}</p>
                    @if ($summary['amount_delta'] !== null && $summary['amount_delta'] !== 0.0)
                        <p class="wm-quote-badge-meta {{ $summary['amount_delta'] < 0 ? 'is-positive' : 'is-negative' }}">
                            {{ $summary['amount_delta'] < 0 ? 'Savings' : 'Over budget' }}
                            {{ 'EUR ' . number_format(abs($summary['amount_delta']), 2, ',', '.') }}
                        </p>
                    @elseif ($summary['amount_delta'] !== null)
                        <p class="wm-quote-badge-meta">Aligned with the initial estimate</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="wm-dashboard-grid">
            @foreach ($dashboardCards as $card)
                <button
                    type="button"
                    wire:click="setActiveWorkspaceTab('{{ $card['key'] }}')"
                    class="wm-dashboard-card wm-card {{ $this->activeWorkspaceTab === $card['key'] ? 'is-active' : '' }}"
                >
                    <span class="wm-dashboard-copy">
                        <span class="wm-dashboard-label">{{ $card['label'] }}</span>
                        <span class="wm-dashboard-value">{{ $card['value'] }}</span>
                        <span class="wm-dashboard-meta">{{ $card['meta'] }}</span>
                        @if (filled($card['footer'] ?? null))
                            <span class="wm-dashboard-footer">{{ $card['footer'] }}</span>
                        @endif
                    </span>
                    <span class="wm-dashboard-action" aria-hidden="true">
                        <x-heroicon-o-arrow-right />
                    </span>
                </button>
            @endforeach
        </section>

        @if (! $isCustomer && $this->activeWorkspaceTab === 'communications')
        <section id="communications" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Communications</h3>
                        <p class="wm-section-subtitle">Timeline of quote requests, responses and every follow-up communication with this supplier.</p>
                    </div>
                </div>

                <div class="wm-list">
                    @forelse ($communications as $communication)
                        <div class="wm-item">
                            <div class="wm-item-head">
                                <div>
                                    <p class="wm-item-title">{{ $communication->subject ?: (\App\Models\ProjectSupplierCommunication::TYPE_OPTIONS[$communication->communication_type] ?? $communication->communication_type) }}</p>
                                    <p class="wm-item-meta">
                                        {{ \App\Models\ProjectSupplierCommunication::TYPE_OPTIONS[$communication->communication_type] ?? $communication->communication_type }}
                                        · {{ \App\Models\ProjectSupplierCommunication::DIRECTION_OPTIONS[$communication->direction] ?? $communication->direction }}
                                        · {{ $communication->communication_at?->format('d/m/Y H:i') ?? '—' }}
                                    </p>
                                </div>
                                <span class="wm-badge">{{ \App\Models\ProjectSupplierCommunication::DIRECTION_OPTIONS[$communication->direction] ?? $communication->direction }}</span>
                            </div>
                            @if ($communication->message)
                                <p class="wm-item-copy">{{ $communication->message }}</p>
                            @endif
                            @if ($communication->notes)
                                <p class="wm-item-meta" style="margin-top:.55rem;">Notes: {{ $communication->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="wm-empty">No communication registered yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Add communication</h3>
                        <p class="wm-section-subtitle">Log emails, calls, meetings, site visits and any later update.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="communication-type">Communication type</label>
                            <select id="communication-type" class="wm-select" wire:model="communicationForm.communication_type">
                                @foreach (\App\Models\ProjectSupplierCommunication::TYPE_OPTIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wm-label" for="communication-direction">Direction</label>
                            <select id="communication-direction" class="wm-select" wire:model="communicationForm.direction">
                                @foreach (\App\Models\ProjectSupplierCommunication::DIRECTION_OPTIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="communication-at">Date and time</label>
                            <input id="communication-at" type="datetime-local" class="wm-field" wire:model="communicationForm.communication_at">
                        </div>
                        <div>
                            <label class="wm-label" for="communication-subject">Subject</label>
                            <input id="communication-subject" type="text" class="wm-field" wire:model="communicationForm.subject">
                        </div>
                    </div>

                    <div>
                        <label class="wm-label" for="communication-message">Message</label>
                        <textarea id="communication-message" class="wm-textarea" wire:model="communicationForm.message"></textarea>
                    </div>

                    <div>
                        <label class="wm-label" for="communication-notes">Notes</label>
                        <textarea id="communication-notes" class="wm-textarea" wire:model="communicationForm.notes"></textarea>
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveCommunication">Save communication</x-filament::button>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'documents')
        <section id="documents" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Documents</h3>
                        <p class="wm-section-subtitle">Quote files, contracts, invoices and receipts are stored here with upload date always visible.</p>
                    </div>
                </div>

                <div class="wm-doc-groups">
                    @php
                        $documentGroups = [
                            'Quote' => $quoteDocuments,
                            'Contracts' => $contractDocuments,
                            'Signed contracts' => $signedContractDocuments,
                            'Invoices' => $invoiceDocuments,
                            'Payment receipts' => $paymentReceiptDocuments,
                            'Other documents' => $otherDocuments,
                        ];
                    @endphp

                    @foreach ($documentGroups as $groupLabel => $documents)
                        <div class="wm-doc-group">
                            <h4 class="wm-doc-group-title">{{ $groupLabel }}</h4>
                            @forelse ($documents as $document)
                                <div class="wm-item">
                                    <div class="wm-item-head">
                                        <div>
                                            <p class="wm-item-title">{{ $document->title }}</p>
                                            <p class="wm-item-meta">
                                                {{ \App\Models\ProjectDocument::TYPE_OPTIONS[$document->type] ?? $document->type }}
                                                · uploaded {{ $document->created_at?->format('d/m/Y H:i') ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                    @if ($document->description)
                                        <p class="wm-item-copy">{{ $document->description }}</p>
                                    @endif
                                    <div class="wm-actions">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}" target="_blank" class="wm-link">Open</a>
                                        @if (! $isCustomer)
                                            <button type="button" class="wm-link" wire:click="deleteDocument({{ $document->id }})">Delete</button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="wm-empty">No {{ strtolower($groupLabel) }} yet.</div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            </div>

            @if (! $isCustomer)
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Upload document</h3>
                        <p class="wm-section-subtitle">Accepted quote documents already appear here as type <code>Quote</code>. Add the remaining files from this panel.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="document-type">Document type</label>
                            <select id="document-type" class="wm-select" wire:model="documentForm.type">
                                @foreach (\App\Models\ProjectDocument::TYPE_OPTIONS as $value => $label)
                                    @if ($value !== \App\Models\ProjectDocument::TYPE_PAYMENT_RECEIPT)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wm-label" for="document-title">Title</label>
                            <input id="document-title" type="text" class="wm-field" wire:model="documentForm.title">
                        </div>
                    </div>

                    <div>
                        <label class="wm-label" for="document-description">Description</label>
                        <textarea id="document-description" class="wm-textarea" wire:model="documentForm.description"></textarea>
                    </div>

                    <div>
                        <label class="wm-label" for="document-upload">File</label>
                        <input id="document-upload" type="file" class="wm-field" wire:model="documentUpload">
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveDocument">Save document</x-filament::button>
                    </div>
                </div>
            </div>
            @endif
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'photogallery')
        <section id="photogallery" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Photogallery</h3>
                        <p class="wm-section-subtitle">Visual gallery for dashboard use, client-facing selections and internal inspiration.</p>
                    </div>
                </div>

                <div class="wm-gallery-grid">
                    @forelse ($images as $image)
                        <div class="wm-gallery-card">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="{{ $image->description ?: 'Project image' }}">
                            <div class="wm-gallery-copy">
                                <p class="wm-item-title">{{ \App\Models\ProjectImage::CATEGORY_OPTIONS[$image->image_category] ?? $image->image_category }}</p>
                                <p class="wm-item-meta">
                                    {{ $image->is_client_visible ? 'Visible to client' : 'Internal only' }}
                                    · uploaded {{ $image->created_at?->format('d/m/Y') ?? '—' }}
                                </p>
                                <p class="wm-item-copy" style="margin-top:.45rem;">{{ $image->description ?: 'No description' }}</p>
                                <div class="wm-actions">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" target="_blank" class="wm-link">Open</a>
                                    @if (! $isCustomer)
                                        <button type="button" class="wm-link" wire:click="deleteImage({{ $image->id }})">Delete</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="wm-empty">No images uploaded yet.</div>
                    @endforelse
                </div>
            </div>

            @if (! $isCustomer)
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Add image</h3>
                        <p class="wm-section-subtitle">Keep the gallery curated with category, note and visibility for client presentations.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div>
                        <label class="wm-label" for="image-upload">Image</label>
                        <input id="image-upload" type="file" class="wm-field" wire:model="imageUpload">
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="image-category">Image category</label>
                            <select id="image-category" class="wm-select" wire:model="imageForm.image_category">
                                @foreach (\App\Models\ProjectImage::CATEGORY_OPTIONS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex;align-items:end;">
                            <label style="display:inline-flex;gap:.6rem;align-items:center;color:#4d473f;font-weight:600;">
                                <input type="checkbox" wire:model="imageForm.is_client_visible">
                                <span>Visible in client presentations</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="wm-label" for="image-description">Description</label>
                        <textarea id="image-description" class="wm-textarea" wire:model="imageForm.description"></textarea>
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveImage">Save image</x-filament::button>
                    </div>
                </div>
            </div>
            @endif
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'payments')
        <section id="payments" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Payments</h3>
                        <p class="wm-section-subtitle">Track deposits, balances, invoice references and receipts tied to this supplier.</p>
                    </div>
                </div>

                <div class="wm-list">
                    @forelse ($payments as $payment)
                        <div class="wm-item">
                            <div class="wm-item-head">
                                <div>
                                    <p class="wm-item-title">{{ $payment->reason }}</p>
                                    <p class="wm-item-meta">
                                        EUR {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                        · due {{ $payment->due_date?->format('d/m/Y') ?? '—' }}
                                        @if ($payment->paymentMode?->name)
                                            · {{ $payment->paymentMode->name }}
                                        @endif
                                        · {{ \App\Models\Payment::STATUS_OPTIONS[$payment->payment_status] ?? $payment->payment_status }}
                                        @if ($payment->paid_at)
                                            · paid {{ $payment->paid_at->format('d/m/Y') }}
                                        @endif
                                        @if ($payment->invoice_reference)
                                            · invoice {{ $payment->invoice_reference }}
                                        @endif
                                    </p>
                                </div>
                                <span class="wm-badge">{{ \App\Models\Payment::STATUS_OPTIONS[$payment->payment_status] ?? $payment->payment_status }}</span>
                            </div>
                            @if ($payment->notes)
                                <p class="wm-item-copy">{{ $payment->notes }}</p>
                            @endif
                            <div class="wm-actions">
                                @if ($payment->paymentReceiptDocument)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->paymentReceiptDocument->file_path) }}" target="_blank" class="wm-link">Open receipt</a>
                                @endif
                                @if (! $isCustomer && $payment->payment_status === \App\Models\Payment::STATUS_UNPAID)
                                    <button type="button" class="wm-link" wire:click="startPaymentRegistration({{ $payment->id }})">Register payment</button>
                                @endif
                                @if (! $isCustomer)
                                    <button type="button" class="wm-link" wire:click="deletePayment({{ $payment->id }})">Delete</button>
                                @endif
                            </div>

                            @if (! $isCustomer && ($this->openPaymentRegistrations[$payment->id] ?? false) && $payment->payment_status === \App\Models\Payment::STATUS_UNPAID)
                                <div class="wm-inline-form">
                                    <div class="wm-inline-grid">
                                        <div>
                                            <label class="wm-label" for="register-payment-paid-at-{{ $payment->id }}">Payment date</label>
                                            <input id="register-payment-paid-at-{{ $payment->id }}" type="date" class="wm-field" wire:model="paymentCompletionForms.{{ $payment->id }}.paid_at">
                                        </div>
                                        <div>
                                            <label class="wm-label" for="register-payment-receipt-{{ $payment->id }}">Payment receipt</label>
                                            <input id="register-payment-receipt-{{ $payment->id }}" type="file" class="wm-field" wire:model="paymentCompletionReceiptUploads.{{ $payment->id }}">
                                        </div>
                                    </div>

                                    <div class="wm-actions">
                                        <x-filament::button color="primary" wire:click="registerScheduledPayment({{ $payment->id }})">Confirm payment</x-filament::button>
                                        <button type="button" class="wm-link" wire:click="cancelPaymentRegistration({{ $payment->id }})">Cancel</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="wm-empty">No payments recorded yet.</div>
                    @endforelse
                </div>
            </div>

            @if (! $isCustomer)
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Add payment</h3>
                        <p class="wm-section-subtitle">Register a completed payment or schedule an upcoming one for this supplier.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div>
                        <label class="wm-label">Payment mode</label>
                        <div class="wm-radio-group">
                            <label class="wm-radio-option">
                                <input type="radio" wire:model.live="paymentEntryMode" value="register">
                                <span>Register payment</span>
                            </label>
                            <label class="wm-radio-option">
                                <input type="radio" wire:model.live="paymentEntryMode" value="schedule">
                                <span>Schedule payment</span>
                            </label>
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="payment-mode-id">Payment mode</label>
                            <select id="payment-mode-id" class="wm-select" wire:model="paymentForm.payment_mode_id">
                                <option value="">Select payment mode</option>
                                @foreach ($this->getPaymentModeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="wm-label" for="payment-reason">Reason</label>
                            <input id="payment-reason" type="text" class="wm-field" wire:model="paymentForm.reason">
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="payment-amount">Amount</label>
                            <input id="payment-amount" type="number" step="0.01" class="wm-field" wire:model="paymentForm.amount">
                        </div>
                        <div>
                            <label class="wm-label" for="payment-due-date">Due date</label>
                            <input id="payment-due-date" type="date" class="wm-field" wire:model="paymentForm.due_date">
                        </div>
                    </div>

                    <div class="wm-inline-grid">
                        <div>
                            <label class="wm-label" for="payment-invoice-reference">Invoice reference</label>
                            <input id="payment-invoice-reference" type="text" class="wm-field" wire:model="paymentForm.invoice_reference">
                        </div>
                    </div>

                    @if ($this->paymentEntryMode === 'register')
                        <div class="wm-inline-grid">
                            <div>
                                <label class="wm-label" for="payment-paid-at">Payment date</label>
                                <input id="payment-paid-at" type="date" class="wm-field" wire:model="paymentForm.paid_at">
                            </div>
                        </div>

                        <div>
                            <label class="wm-label" for="payment-receipt">Payment receipt</label>
                            <input id="payment-receipt" type="file" class="wm-field" wire:model="paymentReceiptUpload">
                        </div>
                    @endif

                    <div>
                        <label class="wm-label" for="payment-notes">Notes</label>
                        <textarea id="payment-notes" class="wm-textarea" wire:model="paymentForm.notes"></textarea>
                    </div>

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="savePayment">{{ $this->paymentEntryMode === 'register' ? 'Register payment' : 'Schedule payment' }}</x-filament::button>
                    </div>
                </div>
            </div>
            @endif
        </section>
        @endif

        @if ($this->activeWorkspaceTab === 'checklist')
        <section id="checklist" class="wm-section wm-form">
            <section class="wm-checklist-summary">
                @if (! $isCustomer)
                    <article class="wm-card wm-checklist-stat">
                        <p class="wm-checklist-stat-label">Sections</p>
                        <p class="wm-checklist-stat-value">{{ $checklistSummary['sections'] }}</p>
                        <p class="wm-checklist-stat-meta">Planner, client and supplier task boards.</p>
                    </article>
                @endif
                <article class="wm-card wm-checklist-stat">
                    <p class="wm-checklist-stat-label">Total tasks</p>
                    <p class="wm-checklist-stat-value">{{ $checklistSummary['total'] }}</p>
                    <p class="wm-checklist-stat-meta">Tasks linked to {{ $summary['supplier'] }}.</p>
                </article>
                <article class="wm-card wm-checklist-stat">
                    <p class="wm-checklist-stat-label">Completed</p>
                    <p class="wm-checklist-stat-value">{{ $checklistSummary['completed'] }}</p>
                    <p class="wm-checklist-stat-meta">{{ $checklistSummary['open'] }} still open.</p>
                </article>
                <article class="wm-card wm-checklist-stat">
                    <p class="wm-checklist-stat-label">Due soon</p>
                    <p class="wm-checklist-stat-value">{{ $checklistSummary['due_soon'] }}</p>
                    <p class="wm-checklist-stat-meta">Open tasks due in the next 30 days.</p>
                </article>
            </section>

            <div class="wm-checklist-toolbar">
                <label class="wm-checklist-filter">
                    <input type="checkbox" wire:model.live="hideCompleted">
                    <span>Hide completed</span>
                </label>
            </div>

            <section class="wm-checklist-board">
                @foreach ($checklistSections as $section)
                    <article class="wm-card wm-checklist-section">
                        <div class="wm-checklist-section-head">
                            <div class="wm-checklist-avatar">{{ $section['avatar'] }}</div>

                            <div>
                                <h3 class="wm-checklist-section-title">{{ $section['title'] }}</h3>
                                <p class="wm-checklist-section-subtitle">{{ $section['subtitle'] }}</p>
                            </div>
                        </div>

                        @if (! $isCustomer)
                            <div class="wm-checklist-add-row">
                                @if (str_starts_with($section['key'], 'supplier-'))
                                    <button type="button" class="wm-checklist-add-button" wire:click="addChecklistItem('supplier', {{ $this->proposalRecord->supplier_id ?: 'null' }})">
                                        + Add task
                                    </button>
                                @elseif ($section['key'] === 'client')
                                    <button type="button" class="wm-checklist-add-button" wire:click="addChecklistItem('client', {{ $this->proposalRecord->supplier_id ?: 'null' }})">
                                        + Add task
                                    </button>
                                @else
                                    <button type="button" class="wm-checklist-add-button" wire:click="addChecklistItem('admin', {{ $this->proposalRecord->supplier_id ?: 'null' }})">
                                        + Add task
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if ($section['items']->isEmpty())
                            <div class="wm-checklist-empty">
                                {{ $this->hideCompleted && ($section['total_count'] ?? 0) > 0 ? 'All tasks in this section are completed.' : 'No tasks currently assigned.' }}
                            </div>
                        @else
                            <div class="wm-checklist-items">
                                @foreach ($section['items'] as $item)
                                    @php
                                        $isExpanded = $expandedChecklistItemId === $item->id;
                                        $timeLabel = $item->due_date
                                            ? $item->due_date->format('M j, Y')
                                            : ($item->anticipation ?: 'No timeframe');
                                        $titleLabel = trim((string) ($checklistForms[$item->id]['title'] ?? $item->title ?? ''));
                                        $responseLabel = trim((string) ($checklistForms[$item->id]['response'] ?? $item->response ?? ''));
                                    @endphp

                                    <div class="wm-checklist-row {{ $item->completed ? 'is-completed' : '' }} {{ $isExpanded ? 'is-expanded' : '' }}" wire:key="supplier-checklist-item-{{ $item->id }}">
                                        <input
                                            type="checkbox"
                                            class="wm-checklist-toggle"
                                            @checked($item->completed)
                                            x-on:click.stop
                                            x-on:change="$wire.toggleChecklistCompleted({{ $item->id }}, $event.target.checked)"
                                        >

                                        <div
                                            class="wm-checklist-main"
                                            @if ($isExpanded)
                                                x-data="{ mode: @js($checklistForms[$item->id]['due_date_mode'] ?? 'relative') }"
                                                x-on:mousedown.window="if (! $el.contains($event.target) && ! $event.target.closest('[data-checklist-delete-modal]')) { $wire.collapseChecklistItem() }"
                                            @endif
                                        >
                                            @if (! $isExpanded)
                                                <button type="button" class="wm-checklist-summary-row" wire:click="expandChecklistItem({{ $item->id }})">
                                                    <span class="wm-checklist-summary-copy">
                                                        <span class="wm-checklist-summary-title">{{ $titleLabel !== '' ? $titleLabel : '(Unnamed Task)' }}</span>
                                                        @if (filled($checklistForms[$item->id]['details'] ?? $item->details))
                                                            <span class="wm-checklist-summary-details">{{ trim((string) ($checklistForms[$item->id]['details'] ?? $item->details)) }}</span>
                                                        @endif
                                                        @if ($item->to_be_filled)
                                                            <span class="wm-checklist-summary-details">
                                                                {{ $responseLabel !== '' ? $responseLabel : 'Response required' }}
                                                            </span>
                                                        @endif
                                                    </span>
                                                    <span class="wm-checklist-time">{{ $timeLabel }}</span>
                                                </button>
                                            @else
                                                <div class="wm-checklist-editor">
                                                    @if ($isCustomer)
                                                        <div class="wm-checklist-summary-title">{{ $titleLabel !== '' ? $titleLabel : '(Unnamed Task)' }}</div>
                                                    @else
                                                        <input
                                                            type="text"
                                                            class="wm-checklist-title-input"
                                                            placeholder="Enter a task description"
                                                            wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.title"
                                                        >
                                                    @endif

                                                    @if ($isCustomer)
                                                        @if (filled($checklistForms[$item->id]['details'] ?? $item->details))
                                                            <div class="wm-checklist-summary-details">{{ trim((string) ($checklistForms[$item->id]['details'] ?? $item->details)) }}</div>
                                                        @endif
                                                    @else
                                                        <textarea
                                                            class="wm-checklist-details-input"
                                                            rows="3"
                                                            placeholder="details"
                                                            wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.details"
                                                        ></textarea>
                                                    @endif

                                                    @if (! $isCustomer)
                                                        <label class="wm-checklist-fill-toggle">
                                                            <input type="checkbox" wire:model.live="checklistForms.{{ $item->id }}.to_be_filled">
                                                            <span>Requires response</span>
                                                        </label>

                                                        <label class="wm-checklist-supplier-field">
                                                            <span class="wm-checklist-supplier-label">Supplier</span>
                                                            <select class="wm-checklist-supplier-select" wire:model.live="checklistForms.{{ $item->id }}.supplier_id">
                                                                <option value="">No supplier</option>
                                                                @foreach ($supplierOptions as $supplierId => $supplierName)
                                                                    <option value="{{ $supplierId }}">{{ $supplierName }}</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                    @endif

                                                    @if ($item->to_be_filled || (bool) ($checklistForms[$item->id]['to_be_filled'] ?? false))
                                                        <div class="wm-checklist-response-box">
                                                            <label class="wm-checklist-response-label" for="supplier-checklist-response-{{ $item->id }}">Response</label>
                                                            <textarea
                                                                id="supplier-checklist-response-{{ $item->id }}"
                                                                class="wm-checklist-response-input"
                                                                rows="3"
                                                                placeholder="Write the response"
                                                                wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.response"
                                                            ></textarea>
                                                        </div>
                                                    @endif

                                                    @if (! $isCustomer)
                                                        <div class="wm-checklist-schedule">
                                                            <div class="wm-checklist-schedule-toggle">
                                                                <button
                                                                    type="button"
                                                                    class="wm-checklist-schedule-chip"
                                                                    x-bind:class="{ 'is-active': mode === 'relative' }"
                                                                    x-on:mousedown.stop.prevent="mode = 'relative'; $wire.set('checklistForms.{{ $item->id }}.due_date_mode', 'relative')"
                                                                    x-on:click.stop.prevent
                                                                >
                                                                    Relative
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    class="wm-checklist-schedule-chip"
                                                                    x-bind:class="{ 'is-active': mode === 'exact' }"
                                                                    x-on:mousedown.stop.prevent="mode = 'exact'; $wire.set('checklistForms.{{ $item->id }}.due_date_mode', 'exact')"
                                                                    x-on:click.stop.prevent
                                                                >
                                                                    Exact date
                                                                </button>
                                                            </div>

                                                            <div class="wm-checklist-schedule-date" x-cloak x-show="mode === 'exact'">
                                                                <input type="date" class="wm-checklist-schedule-input" wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.exact_due_date">
                                                            </div>

                                                            <div class="wm-checklist-schedule-grid" x-cloak x-show="mode === 'relative'">
                                                                <input
                                                                    type="number"
                                                                    min="1"
                                                                    class="wm-checklist-schedule-input"
                                                                    placeholder="3"
                                                                    wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.anticipation_value"
                                                                >
                                                                <select class="wm-checklist-schedule-select" wire:model.live.debounce.400ms="checklistForms.{{ $item->id }}.anticipation_unit">
                                                                    <option value="days">Days</option>
                                                                    <option value="weeks">Weeks</option>
                                                                    <option value="months">Months</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="wm-checklist-meta">
                                                        <span class="wm-checklist-pill">{{ $item->checklist?->title ?? 'Checklist' }}</span>
                                                        @if ($item->supplier)
                                                            <span class="wm-checklist-pill">{{ $item->supplier->name }}</span>
                                                        @endif
                                                        @if ($item->completed_at)
                                                            <span class="wm-checklist-pill">Completed {{ $item->completed_at->format('d/m H:i') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="wm-checklist-side">
                                            @if ($isExpanded && ! $isCustomer)
                                                <div class="wm-checklist-actions">
                                                    <button
                                                        type="button"
                                                        class="wm-checklist-action"
                                                        x-on:mousedown.stop
                                                        wire:click.stop="promptDeleteChecklistItem({{ $item->id }})"
                                                        title="Delete task"
                                                    >
                                                        <x-heroicon-o-trash />
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if (! $loop->last)
                                        <div class="wm-checklist-divider"></div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>

            @if ($confirmDeleteChecklistItemId)
                <div class="wm-checklist-modal-backdrop" data-checklist-delete-modal wire:click="cancelDeleteChecklistItem"></div>
                <div
                    class="wm-checklist-modal"
                    data-checklist-delete-modal
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="supplier-checklist-delete-title"
                    x-on:mousedown.stop
                    x-on:click.stop
                >
                    <p id="supplier-checklist-delete-title" class="wm-checklist-modal-copy">
                        Deleting this item will permanently remove it from the project's checklist.
                    </p>

                    <div class="wm-checklist-modal-actions">
                        <button type="button" class="wm-checklist-modal-button" wire:click.stop="cancelDeleteChecklistItem">
                            Cancel
                        </button>
                        <button type="button" class="wm-checklist-modal-button is-danger" wire:click.stop="confirmDeleteChecklistItem">
                            Delete item
                        </button>
                    </div>
                </div>
            @endif
        </section>
        @endif

        @if (! $isCustomer && $this->activeWorkspaceTab === 'commissions')
        <section id="commissions" class="wm-section wm-two-col">
            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Commissions</h3>
                        <p class="wm-section-subtitle">Set the real commission for this event and track commission invoice payments.</p>
                    </div>
                </div>

                <div class="wm-form">
                    <div class="wm-commission-summary">
                        <div class="wm-item">
                            <p class="wm-item-meta">Mode</p>
                            <p class="wm-item-title">{{ $commissionSummary['mode_label'] }}</p>
                        </div>
                        <div class="wm-item">
                            <p class="wm-item-meta">Commission</p>
                            <p class="wm-item-title">EUR {{ number_format($commissionSummary['amount'], 2, ',', '.') }}</p>
                        </div>
                        <div class="wm-item">
                            <p class="wm-item-meta">Paid</p>
                            <p class="wm-item-title">EUR {{ number_format((float) ($this->commissionForm['commission_total_amount_payed'] ?? 0), 2, ',', '.') }}</p>
                        </div>
                        <div class="wm-item">
                            <p class="wm-item-meta">Balance</p>
                            <p class="wm-item-title">
                                EUR {{ number_format(max(0, (float) ($this->commissionForm['commission_amount'] ?? 0) - (float) ($this->commissionForm['commission_total_amount_payed'] ?? 0)), 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="wm-label">Commission mode</label>
                        <div class="wm-radio-group">
                            @foreach (\App\Models\CategoryBudgetSupplier::COMMISSION_MODE_OPTIONS as $value => $label)
                                <label class="wm-radio-option">
                                    <input type="radio" wire:model.live="commissionForm.commission_mode" value="{{ $value }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if (($this->commissionForm['commission_mode'] ?? null) === \App\Models\CategoryBudgetSupplier::COMMISSION_MODE_PERCENTAGE)
                        <div class="wm-inline-grid">
                            <div>
                                <label class="wm-label" for="commission-percentage">Commission percentage</label>
                                <input id="commission-percentage" type="number" min="0" max="100" step="0.01" class="wm-field" wire:model.live="commissionForm.commission_percentage">
                            </div>
                            <div>
                                <label class="wm-label" for="commission-base-amount">Calculation base</label>
                                <input
                                    id="commission-base-amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="wm-field"
                                    wire:model.live="commissionForm.commission_base_amount"
                                    placeholder="{{ number_format((float) ($this->proposalRecord->proposed_amount ?? 0), 2, '.', '') }}"
                                >
                            </div>
                            <div>
                                <label class="wm-label" for="commission-amount-percentage">Commission amount</label>
                                <input id="commission-amount-percentage" type="number" step="0.01" class="wm-field" wire:model="commissionForm.commission_amount" readonly>
                            </div>
                        </div>
                    @elseif (($this->commissionForm['commission_mode'] ?? null) === \App\Models\CategoryBudgetSupplier::COMMISSION_MODE_FIXED)
                        <div class="wm-inline-grid">
                            <div>
                                <label class="wm-label" for="commission-amount-fixed">Commission amount</label>
                                <input id="commission-amount-fixed" type="number" min="0" step="0.01" class="wm-field" wire:model.live="commissionForm.commission_amount">
                            </div>
                        </div>
                    @else
                        <div class="wm-empty">No commission is expected for this supplier on this event.</div>
                    @endif

                    <div class="wm-actions">
                        <x-filament::button color="primary" wire:click="saveCommission">Save commissions</x-filament::button>
                    </div>
                </div>
            </div>

            <div class="wm-card wm-panel">
                <div class="wm-section-head">
                    <div>
                        <h3 class="wm-section-title">Commission payments</h3>
                        <p class="wm-section-subtitle">Each paid date contributes to the total commission paid.</p>
                    </div>
                </div>

                <div class="wm-form">
                    @forelse (($this->commissionForm['commission_payments_json'] ?? []) as $index => $payment)
                        <div class="wm-item">
                            <div class="wm-commission-payment-grid">
                                <div>
                                    <label class="wm-label" for="commission-invoice-date-{{ $index }}">Invoice date</label>
                                    <input id="commission-invoice-date-{{ $index }}" type="date" class="wm-field" wire:model="commissionForm.commission_payments_json.{{ $index }}.invoice_date">
                                </div>
                                <div>
                                    <label class="wm-label" for="commission-due-date-{{ $index }}">Due date</label>
                                    <input id="commission-due-date-{{ $index }}" type="date" class="wm-field" wire:model="commissionForm.commission_payments_json.{{ $index }}.due_date">
                                </div>
                                <div>
                                    <label class="wm-label" for="commission-payment-amount-{{ $index }}">Amount</label>
                                    <input id="commission-payment-amount-{{ $index }}" type="number" min="0" step="0.01" class="wm-field" wire:model.live="commissionForm.commission_payments_json.{{ $index }}.amount">
                                </div>
                                <div>
                                    <label class="wm-label" for="commission-paid-at-{{ $index }}">Paid at</label>
                                    <input id="commission-paid-at-{{ $index }}" type="date" class="wm-field" wire:model.live="commissionForm.commission_payments_json.{{ $index }}.paid_at">
                                </div>
                                <div class="is-row-break">
                                    <label class="wm-label" for="commission-payment-type-{{ $index }}">Payment type</label>
                                    <select id="commission-payment-type-{{ $index }}" class="wm-select" wire:model="commissionForm.commission_payments_json.{{ $index }}.payment_type_id">
                                        <option value="">Select payment mode</option>
                                        @foreach ($this->getPaymentModeOptions() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="is-wide">
                                    <label class="wm-label" for="commission-payment-note-{{ $index }}">Note</label>
                                    <input id="commission-payment-note-{{ $index }}" type="text" class="wm-field" wire:model="commissionForm.commission_payments_json.{{ $index }}.note" placeholder="Internal note">
                                </div>
                                <button type="button" class="wm-commission-delete-button" wire:click="removeCommissionPayment({{ $index }})" title="Delete payment">
                                    <x-heroicon-o-trash />
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="wm-empty">No commission payments annotated yet.</div>
                    @endforelse

                    <div class="wm-actions">
                        <x-filament::button color="gray" wire:click="addCommissionPayment">Add payment</x-filament::button>
                    </div>
                </div>
            </div>
        </section>
        @endif
    </div>
</x-filament-panels::page>
