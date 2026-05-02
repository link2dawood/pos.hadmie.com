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

        .studio-print-btn,
        .studio-download-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 26px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            border: none;
            border-radius: 100px;
            cursor: pointer;
            transition: transform 0.14s cubic-bezier(.34,1.56,.64,1), box-shadow 0.25s ease;
        }

        .studio-print-btn {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            color: #1a0e00;
            box-shadow: 0 4px 20px var(--gold-glow), inset 0 1px 0 rgba(255,255,255,0.25);
            animation: btn-glow 3.5s ease-in-out infinite;
        }

        .studio-download-btn {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.88);
            border: 1px solid rgba(255,255,255,0.15);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .studio-print-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 36px var(--gold-glow), inset 0 1px 0 rgba(255,255,255,0.3);
            animation: none;
        }

        .studio-download-btn:hover {
            transform: translateY(-2px) scale(1.01);
            background: rgba(255,255,255,0.13);
            box-shadow: 0 6px 18px rgba(0,0,0,0.3);
        }

        .studio-print-btn:active,
        .studio-download-btn:active {
            transform: scale(0.97) translateY(0);
        }

        .studio-print-btn:active  { box-shadow: 0 2px 10px var(--gold-glow); }
        .studio-download-btn:active { box-shadow: 0 1px 6px rgba(0,0,0,0.2); }

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

        /* ── Label card styles (shared partial) ─────────────────────── */

        @media print {
            body, html {
                background: #ffffff !important;
                background-image: none !important;
                min-height: 0 !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .studio-bar { display: none !important; }

            .label-preview {
                padding: 0 !important;
                margin: 0 !important;
                gap: 0;
                display: block;
            }

            .label-sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0 !important;
                animation: none;
                max-width: none !important;
                width: {{ $paper_width }}in !important;
                margin: 0 !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .label-sheet__table {
                border-collapse: collapse !important;
                border-spacing: 0 !important;
                margin: 0 !important;
            }
            
            .label-sheet__cell {
                padding: 0 !important;
            }

            .label-card {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin: 0 !important;
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
    @include('labels.partials.label_card_styles')
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

            <button class="studio-download-btn" type="button" onclick="downloadPNG()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download PNG
            </button>

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

    <script>
        /* ── Fix QR/barcode image wrapper heights ─────────────────────
           position:absolute children need a non-zero parent height.
           flex:1 alone is unreliable when all children are out-of-flow,
           so we measure each .label-card__code and set explicit px height
           on its .label-card__img-wrap after layout settles. */
        function fixImgWrapHeights() {
            document.querySelectorAll('.label-card__code').forEach(function(code) {
                var wrap = code.querySelector('.label-card__img-wrap');
                if (!wrap) return;

                var codeRect  = code.getBoundingClientRect();
                var textEl    = code.querySelector('.label-card__code-text');
                var textH     = textEl ? (textEl.getBoundingClientRect().height + 3) : 0;
                var wrapH     = Math.max(0, codeRect.height - textH);

                if (wrapH > 0) {
                    wrap.style.position = 'relative';
                    wrap.style.height   = wrapH + 'px';
                    wrap.style.flex     = 'none';
                }
            });
        }

        /* Run after paint so CSS layout is fully resolved. */
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                requestAnimationFrame(function() { setTimeout(fixImgWrapHeights, 60); });
            });
        } else {
            requestAnimationFrame(function() { setTimeout(fixImgWrapHeights, 60); });
        }

        /* ── Download PNG ───────────────────────────────────────────── */
        function downloadPNG() {
            var sheets = document.querySelectorAll('.label-sheet');
            if (sheets.length === 0) return;

            var originalTitle = document.title;
            document.title = 'labels';

            var promises = Array.from(sheets).map(function(sheet, index) {
                return html2canvas(sheet, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#ffffff'
                }).then(function(canvas) {
                    var link = document.createElement('a');
                    link.download = 'label-sheet-' + (index + 1) + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            });

            Promise.all(promises).then(function() {
                document.title = originalTitle;
            });
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</body>
</html>
