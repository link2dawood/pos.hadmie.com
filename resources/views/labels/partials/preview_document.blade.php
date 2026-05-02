<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('barcode.print_labels') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Outfit:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style type="text/css">
        /* ── Design tokens ─────────────────────────────────────────── */
        :root {
            --ink:          #080e1c;
            --ink-mid:      #0f1829;
            --paper:        #ffffff;
            --brand:        #1d3557;
            --brand-deep:   #0f1e33;
            --gold:         #c9922a;
            --gold-light:   #e8b84b;
            --gold-glow:    rgba(201, 146, 42, 0.35);
            --muted:        #7a8fa6;
            --success:      #34d399;
            --label-border: #1d3557;
            --label-accent: #1d3557;
            --label-text:   #111827;
            --label-muted:  #4b5563;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Screen body ───────────────────────────────────────────── */
        body {
            background-color: var(--ink);
            background-image:
                radial-gradient(circle at 50% 0%, rgba(29, 53, 87, 0.18) 0%, transparent 60%),
                radial-gradient(circle, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 100% 100%, 22px 22px;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--paper);
        }

        /* ── Sticky toolbar ────────────────────────────────────────── */
        .studio-bar {
            position: sticky;
            top: 0;
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 36px;
            background: rgba(8, 14, 28, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.055);
        }

        .studio-bar__left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .studio-bar__icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--brand) 0%, #2a4a72 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 12px rgba(29, 53, 87, 0.5);
            flex-shrink: 0;
        }

        .studio-bar__wordmark {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .studio-bar__title {
            font-size: 15px;
            font-weight: 700;
            color: rgba(255,255,255,0.95);
            letter-spacing: 0.01em;
            line-height: 1;
        }

        .studio-bar__subtitle {
            font-size: 11px;
            font-weight: 500;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            line-height: 1;
        }

        .studio-bar__right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .studio-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--success);
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .studio-status__dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            animation: dot-pulse 2.4s ease-in-out infinite;
            flex-shrink: 0;
        }

        @keyframes dot-pulse {
            0%, 100% { opacity: 1;   box-shadow: 0 0 0 0   rgba(52,211,153,0.5); }
            50%       { opacity: 0.6; box-shadow: 0 0 0 6px rgba(52,211,153,0);   }
        }

        .studio-print-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 26px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            color: #1a0e00;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            box-shadow: 0 4px 20px var(--gold-glow), inset 0 1px 0 rgba(255,255,255,0.25);
            transition: transform 0.14s cubic-bezier(.34,1.56,.64,1), box-shadow 0.25s ease;
            animation: btn-glow 3.5s ease-in-out infinite;
        }

        .studio-print-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 36px var(--gold-glow), inset 0 1px 0 rgba(255,255,255,0.3);
            animation: none;
        }

        .studio-print-btn:active {
            transform: scale(0.97) translateY(0);
            box-shadow: 0 2px 10px var(--gold-glow);
        }

        @keyframes btn-glow {
            0%, 100% { box-shadow: 0 4px 20px rgba(201,146,42,0.3), inset 0 1px 0 rgba(255,255,255,0.2); }
            50%       { box-shadow: 0 4px 28px rgba(201,146,42,0.55), inset 0 1px 0 rgba(255,255,255,0.2); }
        }

        /* ── Preview area ──────────────────────────────────────────── */
        .label-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
            padding: 44px 24px 72px;
        }

        /* ── Label sheet (one page worth of stickers) ──────────────── */
        .label-sheet {
            width: {{ $paper_width }}in;
            max-width: calc(100vw - 48px);
            background: var(--paper);
            border-radius: 10px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.06),
                0 2px  4px rgba(0,0,0,0.25),
                0 12px 32px rgba(0,0,0,0.45),
                0 40px 80px rgba(0,0,0,0.35);
            padding: 10px 12px;
            break-after: page;
            page-break-after: always;
            animation: sheet-appear 0.55s cubic-bezier(.22,1,.36,1) both;
        }

        .label-sheet:nth-child(2) { animation-delay: 0.08s; }
        .label-sheet:nth-child(3) { animation-delay: 0.16s; }
        .label-sheet:nth-child(4) { animation-delay: 0.24s; }

        @keyframes sheet-appear {
            from { opacity: 0; transform: translateY(24px) scale(0.985); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        .label-sheet:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        .label-sheet__table { width: 100%; }

        .label-sheet__cell {
            border: 0;
            padding: 0;
            text-align: center;
            vertical-align: middle;
        }

        /* ── Label card ────────────────────────────────────────────── */
        .label-card {
            width: {{ $barcode_details->width }}in;
            height: {{ $barcode_details->height }}in;
            padding: 0.05in;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }

        .label-card__inner {
            width: 100%;
            height: 100%;
            border: 2.5px solid var(--label-border);
            border-radius: 18px;
            background: #ffffff;
            padding: 0.07in 0.1in 0.06in;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            text-align: left;
        }

        .label-card__business {
            margin: 0 0 2px;
            color: var(--label-accent);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            line-height: 1.2;
            font-family: 'Outfit', Arial, sans-serif;
        }

        .label-card__name {
            margin: 0;
            color: var(--label-accent);
            font-family: 'Outfit', Arial, sans-serif;
            font-weight: 800;
            line-height: 1.1;
            text-transform: uppercase;
            word-break: break-word;
            letter-spacing: 0.02em;
        }

        .label-card__variation,
        .label-card__meta {
            margin-top: 3px;
            color: var(--label-muted);
            font-family: Arial, sans-serif;
            line-height: 1.2;
            word-break: break-word;
        }

        .label-card__meta-line {
            display: block;
            margin-top: 2px;
        }

        .label-card__price {
            margin: 4px 0 0;
            color: #111111;
            font-family: Arial, sans-serif;
            line-height: 1.2;
            word-break: break-word;
        }

        .label-card__price-label { font-weight: 500; }

        .label-card__price-value { font-weight: 700; }

        .label-card__codes {
            margin-top: 6px;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 0;
            overflow: hidden;
            width: 100%;
        }

        .label-card__codes--both { align-items: stretch; }

        .label-card__code {
            display: flex;
            flex: 1 1 100%;
            width: 100%;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
            min-height: 0;
        }

        /* Barcode: stretch width, fixed height */
        .label-card__barcode-image {
            display: block;
            width: 100%;
            flex: 1;
            min-height: 0;
            object-fit: fill;
        }

        /* QR Code: stretched to match the user's explicit pixel-perfect request */
        .label-card__qr-image {
            display: block;
            width: 100%;
            flex: 1;
            min-height: 0;
            object-fit: fill;
        }

        .label-card__code-text {
            margin-top: 3px;
            color: #111111;
            font-family: 'DM Mono', 'Courier New', monospace;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.05em;
            line-height: 1.1;
            text-align: center;
            word-break: break-all;
        }

        /* ── Print overrides ────────────────────────────────────────── */
        @media print {
            body {
                background: #ffffff !important;
                background-image: none !important;
            }

            .studio-bar { display: none !important; }

            .label-preview {
                padding: 0;
                gap: 0;
                display: block;
            }

            .label-sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                animation: none;
            }
        }

        @page {
            size: {{ $paper_width }}in @if($paper_height != 0){{ $paper_height }}in @endif;
            margin-top:    {{ $margin_top }}in;
            margin-right:  {{ $margin_left }}in;
            margin-bottom: {{ $margin_top }}in;
            margin-left:   {{ $margin_left }}in;
        }
    </style>
</head>
<body>

    {{-- ── Toolbar (screen only) ─────────────────────────────────── --}}
    <div class="studio-bar">
        <div class="studio-bar__left">
            <div class="studio-bar__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                     fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 5a2 2 0 0 1 2-2h1v18H5a2 2 0 0 1-2-2V5z"/>
                    <path d="M8 3h1v18H8z"/>
                    <path d="M12 3h2v18h-2z"/>
                    <path d="M17 3h1v18h-1z"/>
                    <path d="M21 5a2 2 0 0 0-2-2h-1v18h1a2 2 0 0 0 2-2V5z"/>
                </svg>
            </div>
            <div class="studio-bar__wordmark">
                <div class="studio-bar__title">Label Print Studio</div>
                <div class="studio-bar__subtitle">Print Preview</div>
            </div>
        </div>

        <div class="studio-bar__right">
            <div class="studio-status">
                <div class="studio-status__dot"></div>
                Ready to Print
            </div>

            <button class="studio-print-btn" type="button" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                {{ __('messages.print') }}
            </button>
        </div>
    </div>

    {{-- ── Label preview ──────────────────────────────────────────── --}}
    <div class="label-preview">
        {!! $pages_html !!}
    </div>

</body>
</html>
