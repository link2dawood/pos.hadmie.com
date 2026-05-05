.print-document {
    color: var(--print-fg);
    font-family: var(--print-font-family);
    font-size: var(--print-font-size-md);
    line-height: 1.45;
}

.print-sheet {
    background: var(--print-bg);
    box-sizing: border-box;
    margin: 0 auto;
    max-width: var(--print-sheet-width);
    padding: var(--print-space-5);
}

.print-section {
    margin-bottom: var(--print-space-5);
}

.print-stack > * + * {
    margin-top: var(--print-space-2);
}

.print-divider {
    border-top: var(--print-border-thin) solid var(--print-line);
    margin: var(--print-space-4) 0;
}

.print-heading {
    font-size: var(--print-font-size-xs);
    font-weight: 700;
    letter-spacing: 0.08em;
    margin: 0 0 var(--print-space-2);
    text-transform: uppercase;
}

.print-brand {
    align-items: flex-start;
    border-bottom: var(--print-border-strong) solid var(--print-line-strong);
    display: flex;
    gap: var(--print-space-4);
    justify-content: space-between;
    padding-bottom: var(--print-space-4);
}

.print-brand__meta {
    flex: 1 1 auto;
    min-width: 0;
}

.print-brand__name {
    font-size: var(--print-font-size-xl);
    font-weight: 700;
    margin: 0;
}

.print-brand__title {
    font-size: var(--print-font-size-sm);
    font-weight: 700;
    letter-spacing: 0.12em;
    margin: var(--print-space-1) 0 0;
    text-transform: uppercase;
}

.print-brand__logo {
    height: auto;
    max-height: 64px;
    max-width: 180px;
    object-fit: contain;
}

.print-grid {
    display: grid;
    gap: var(--print-space-4);
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.print-card {
    background: var(--print-surface);
    border: var(--print-border-thin) solid var(--print-line);
    border-radius: var(--print-radius-sm);
    padding: var(--print-space-3);
}

.print-party__name,
.print-meta__value-strong {
    font-size: var(--print-font-size-lg);
    font-weight: 700;
}

.print-lines {
    color: var(--print-fg);
}

.print-lines > * + * {
    margin-top: var(--print-space-1);
}

.print-lines--muted {
    color: var(--print-muted);
}

.print-meta-grid {
    display: grid;
    gap: var(--print-space-3);
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.print-meta__label {
    color: var(--print-muted);
    font-size: var(--print-font-size-xs);
    text-transform: uppercase;
}

.print-table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
}

.print-table th,
.print-table td {
    border-bottom: var(--print-border-thin) solid var(--print-line);
    padding: var(--print-space-2) var(--print-space-2);
    vertical-align: top;
    word-break: break-word;
}

.print-table thead th {
    border-bottom: var(--print-border-strong) solid var(--print-line-strong);
    color: var(--print-muted);
    font-size: var(--print-font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
}

.print-table thead {
    display: table-header-group;
}

.print-table__cell--right {
    text-align: right;
}

.print-table__cell--center {
    text-align: center;
}

.print-table__item {
    font-weight: 600;
}

.print-table__details {
    color: var(--print-muted);
    font-size: var(--print-font-size-sm);
    margin-top: var(--print-space-1);
    overflow-wrap: anywhere;
}

.print-totals {
    margin-left: auto;
    max-width: 320px;
    width: 100%;
}

.print-totals__row {
    align-items: baseline;
    border-bottom: var(--print-border-thin) solid var(--print-line);
    display: flex;
    gap: var(--print-space-3);
    justify-content: space-between;
    padding: var(--print-space-2) 0;
}

.print-totals__row span:last-child {
    text-align: right;
}

.print-totals__row--strong {
    border-bottom-color: var(--print-line-strong);
    font-size: var(--print-font-size-lg);
    font-weight: 700;
}

.print-note-block {
    border: var(--print-border-thin) solid var(--print-line);
    border-radius: var(--print-radius-sm);
    padding: var(--print-space-3);
}

.print-signatures {
    display: grid;
    gap: var(--print-space-4);
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.print-signature {
    border-bottom: var(--print-border-thin) solid var(--print-line-strong);
    min-height: 56px;
    padding-top: var(--print-space-5);
}

.print-signature__label {
    color: var(--print-muted);
    font-size: var(--print-font-size-xs);
    margin-top: var(--print-space-2);
    text-transform: uppercase;
}

.print-codes {
    align-items: flex-start;
    display: flex;
    gap: var(--print-space-4);
    justify-content: space-between;
}

.print-code {
    flex: 1 1 0;
    text-align: center;
}

.print-code img {
    display: inline-block;
    max-width: 100%;
}

@media print {
    .print-section,
    .print-brand,
    .print-card,
    .print-note-block,
    .print-totals,
    .print-signatures,
    .print-footer,
    .print-codes {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}

.print-footer {
    border-top: var(--print-border-thin) solid var(--print-line);
    color: var(--print-muted);
    font-size: var(--print-font-size-sm);
    padding-top: var(--print-space-3);
}

@media print {
    body {
        background: #fff;
        margin: 0;
    }

    .print-sheet {
        box-shadow: none;
        max-width: none;
    }
}
