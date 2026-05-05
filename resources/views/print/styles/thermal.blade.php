.print-paper-thermal-80,
.print-paper-thermal-58 {
    font-size: var(--print-font-size-sm);
}

.print-paper-thermal-80 .print-sheet,
.print-paper-thermal-58 .print-sheet {
    max-width: var(--print-sheet-width);
    padding: var(--print-space-3);
}

.print-paper-thermal-80 .print-brand,
.print-paper-thermal-58 .print-brand,
.print-paper-thermal-80 .print-codes,
.print-paper-thermal-58 .print-codes,
.print-paper-thermal-80 .print-grid,
.print-paper-thermal-58 .print-grid,
.print-paper-thermal-80 .print-signatures,
.print-paper-thermal-58 .print-signatures {
    display: block;
}

.print-paper-thermal-80 .print-card,
.print-paper-thermal-58 .print-card {
    margin-bottom: var(--print-space-3);
}

.print-paper-thermal-80 .print-meta-grid,
.print-paper-thermal-58 .print-meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.print-paper-thermal-80 .print-table,
.print-paper-thermal-58 .print-table {
    table-layout: auto;
}

.print-paper-thermal-80 .print-table th,
.print-paper-thermal-58 .print-table th,
.print-paper-thermal-80 .print-table td,
.print-paper-thermal-58 .print-table td {
    padding-left: 0;
    padding-right: 0;
}

.print-paper-thermal-80 .print-table__item,
.print-paper-thermal-58 .print-table__item {
    font-size: var(--print-font-size-md);
}

.print-paper-thermal-80 .print-totals__row,
.print-paper-thermal-58 .print-totals__row {
    font-size: var(--print-font-size-md);
}

.print-paper-thermal-80 .print-code + .print-code,
.print-paper-thermal-58 .print-code + .print-code {
    margin-top: var(--print-space-3);
}

.print-paper-thermal-80.print-variant-compact .print-card,
.print-paper-thermal-58.print-variant-compact .print-card,
.print-paper-thermal-80.print-variant-compact .print-note-block,
.print-paper-thermal-58.print-variant-compact .print-note-block {
    padding: var(--print-space-2);
}

.print-paper-thermal-80 .print-totals,
.print-paper-thermal-58 .print-totals {
    max-width: none;
}

.print-paper-thermal-58 {
    --print-font-size-xs: 8px;
    --print-font-size-sm: 9px;
    --print-font-size-md: 10px;
    --print-font-size-lg: 11px;
    --print-font-size-xl: 14px;
    --print-space-1: 2px;
    --print-space-2: 4px;
    --print-space-3: 6px;
    --print-space-4: 10px;
    --print-space-5: 12px;
}

.print-paper-thermal-80 {
    --print-font-size-xs: 9px;
    --print-font-size-sm: 10px;
    --print-font-size-md: 11px;
    --print-font-size-lg: 12px;
    --print-font-size-xl: 15px;
    --print-space-1: 3px;
    --print-space-2: 6px;
    --print-space-3: 8px;
    --print-space-4: 12px;
    --print-space-5: 16px;
}
