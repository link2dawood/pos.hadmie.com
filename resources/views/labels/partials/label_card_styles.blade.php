<style>
    /* ── Label card design tokens ──────────────────────────────── */
    .label-card, .label-card * {
        --label-border: #1d3557;
        --label-accent: #1d3557;
        --label-text:   #111827;
        --label-muted:  #4b5563;
        box-sizing: border-box;
    }

    /* ── Card shell ────────────────────────────────────────────── */
    .label-card {
        padding: 0.03in;
        display: flex;
        align-items: stretch;
        justify-content: center;
        flex-shrink: 0;
    }

    .label-card__inner {
        width: 100%;
        height: 100%;
        border: 2.5px solid var(--label-border);
        border-radius: 10px;
        background: #ffffff;
        padding: 0.04in 0.06in 0.03in;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        text-align: center;
    }

    /* ── Text rows ─────────────────────────────────────────────── */
    .label-card__business {
        margin: 0;
        color: var(--label-accent);
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        line-height: 1.0;
        font-family: 'Outfit', Arial, sans-serif;
    }

    .label-card__name {
        margin: 0;
        color: var(--label-accent);
        font-family: 'Outfit', Arial, sans-serif;
        font-weight: 800;
        line-height: 1.0;
        text-transform: uppercase;
        word-break: break-word;
        letter-spacing: 0.02em;
    }

    .label-card__variation,
    .label-card__meta {
        margin-top: 1px;
        color: var(--label-muted);
        font-family: Arial, sans-serif;
        line-height: 1.1;
        word-break: break-word;
    }

    .label-card__meta-line {
        display: block;
        margin-top: 1px;
    }

    .label-card__price {
        margin: 1px 0 0;
        color: #111111;
        font-family: Arial, sans-serif;
        line-height: 1.1;
        word-break: break-word;
    }

    .label-card__price-label { font-weight: 500; }
    .label-card__price-value { font-weight: 700; }

    /* ── Codes section (fills all remaining vertical space) ─────── */
    .label-card__codes {
        margin-top: 2px;
        flex: 1 1 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        min-height: 0;
        overflow: hidden;
        width: 100%;
    }

    .label-card__code {
        display: flex;
        flex: 1 1 0;
        width: 100%;
        flex-direction: column;
        align-items: center;
        min-width: 0;
        min-height: 0;
    }

    /* Wrapper gets explicit px height from JS so the absolutely
       positioned image fills it reliably in all browsers. */
    .label-card__img-wrap {
        position: relative;
        flex: 1 1 0;
        min-height: 4px;
        width: 100%;
        overflow: hidden;
    }

    /* QR: square container — height drives flex, width matches via aspect-ratio. */
    .label-card__img-wrap--qr {
        flex: 1 1 0;
        aspect-ratio: 1 / 1;
        width: unset;
        max-width: 100%;
        align-self: center;
    }

    /* Barcode: full width, centered. */
    .label-card__img-wrap--barcode {
        width: 100%;
        align-self: center;
    }

    .label-card__qr-image,
    .label-card__barcode-image {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
    }

    /* QR: fill the square container. */
    .label-card__qr-image     { object-fit: fill; }

    /* Barcode: contain preserves bar widths — critical for scanners. */
    .label-card__barcode-image { object-fit: contain; }

    .label-card__code-text {
        margin-top: 1px;
        color: #111111;
        font-family: 'DM Mono', 'Courier New', monospace;
        font-size: 7px;
        font-weight: 500;
        letter-spacing: 0.03em;
        line-height: 1.0;
        text-align: center;
        word-break: break-all;
    }

    .label-card__code-text--barcode {
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.06em;
        white-space: nowrap;
        overflow: hidden;
        width: 100%;
    }
</style>
