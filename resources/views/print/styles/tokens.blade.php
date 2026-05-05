:root {
    --print-bg: #ffffff;
    --print-fg: #111827;
    --print-muted: #6b7280;
    --print-line: #d1d5db;
    --print-line-strong: #111827;
    --print-surface: #f8fafc;
    --print-accent: #111827;
    --print-font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    --print-font-size-xs: 10px;
    --print-font-size-sm: 11px;
    --print-font-size-md: 12px;
    --print-font-size-lg: 14px;
    --print-font-size-xl: 18px;
    --print-space-1: 4px;
    --print-space-2: 8px;
    --print-space-3: 12px;
    --print-space-4: 16px;
    --print-space-5: 24px;
    --print-space-6: 32px;
    --print-radius-sm: 4px;
    --print-border-thin: 1px;
    --print-border-strong: 2px;
    --print-sheet-width: {{ $document['paper_profile']['content_width'] }};
}

@page {
    size: {{ $document['paper_profile']['document_width'] }} auto;
    margin: 0;
}
