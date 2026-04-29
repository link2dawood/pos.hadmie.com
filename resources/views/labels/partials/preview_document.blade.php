<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('barcode.print_labels') }}</title>
    <style type="text/css">
        :root {
            --label-accent: #1d3557;
            --label-border: #243b53;
            --label-text: #111827;
            --label-muted: #4b5563;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 18px;
            background: #eef2f7;
            color: var(--label-text);
            font-family: Arial, sans-serif;
        }

        .label-toolbar {
            display: flex;
            justify-content: center;
            margin: 0 auto 18px;
        }

        .label-toolbar button {
            border: 0;
            border-radius: 999px;
            padding: 10px 18px;
            background: var(--label-accent);
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
        }

        .label-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .label-sheet {
            width: {{ $paper_width }}in;
            max-width: 100%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 18px 46px rgba(15, 23, 42, 0.12);
            padding: 10px 12px;
            break-after: page;
            page-break-after: always;
        }

        .label-sheet:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        .label-sheet__table {
            width: 100%;
        }

        .label-sheet__cell {
            border: 0;
            padding: 0;
            text-align: center;
            vertical-align: middle;
        }

        .label-card {
            width: {{ $barcode_details->width }}in;
            height: {{ $barcode_details->height }}in;
            padding: 0.06in;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }

        .label-card__inner {
            width: 100%;
            height: 100%;
            border: 2px solid var(--label-border);
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 0.08in 0.1in;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .label-card__business {
            margin: 0 0 4px;
            color: var(--label-accent);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .label-card__name {
            margin: 0;
            color: var(--label-accent);
            font-weight: 800;
            line-height: 1.08;
            text-transform: uppercase;
            word-break: break-word;
        }

        .label-card__variation,
        .label-card__meta {
            margin-top: 4px;
            color: var(--label-muted);
            line-height: 1.2;
            word-break: break-word;
        }

        .label-card__meta-line {
            display: block;
            margin-top: 2px;
        }

        .label-card__price {
            margin: 6px 0 0;
            color: #111111;
            line-height: 1.15;
            word-break: break-word;
        }

        .label-card__price-label {
            font-weight: 500;
        }

        .label-card__price-value {
            font-weight: 800;
        }

        .label-card__codes {
            margin-top: 8px;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 0;
        }

        .label-card__codes--both {
            align-items: stretch;
        }

        .label-card__code {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }

        .label-card__code--barcode {
            justify-content: flex-end;
        }

        .label-card__qr-image {
            width: auto;
            max-width: 100%;
            max-height: calc({{ $barcode_details->height }}in * 0.44);
        }

        .label-card__barcode-image {
            width: 100%;
            max-width: 100%;
            max-height: calc({{ $barcode_details->height }}in * 0.19);
            object-fit: contain;
        }

        .label-card__code-text {
            margin-top: 4px;
            color: #111111;
            font-size: 10px;
            line-height: 1.1;
            text-align: center;
            word-break: break-all;
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }

            .label-toolbar {
                display: none !important;
            }

            .label-preview {
                gap: 0;
            }

            .label-sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
        }

        @page {
            size: {{ $paper_width }}in @if($paper_height != 0){{ $paper_height }}in @endif;
            margin-top: {{ $margin_top }}in;
            margin-right: {{ $margin_left }}in;
            margin-bottom: {{ $margin_top }}in;
            margin-left: {{ $margin_left }}in;
        }
    </style>
</head>
<body>
    <div class="label-toolbar">
        <button type="button" onclick="window.print()">{{ __('messages.print') }}</button>
    </div>
    <div class="label-preview">
        {!! $pages_html !!}
    </div>
</body>
</html>
