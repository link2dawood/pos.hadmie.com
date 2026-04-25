# Rollout Checklist — Scan-to-Sell + Document Redesign

Work covered by milestones 1–4. Run through this before switching production traffic to the new build.

---

## 1. Database

- [ ] Run `php artisan migrate` — applies the two barcode migrations:
  - `2026_04_23_120000_add_barcode_and_qr_columns_to_products_table`
  - `2026_04_25_000000_enforce_unique_barcode_and_qr_on_products_table`
- [ ] If the second migration throws a duplicate-value error, run the audit first:
  ```
  php artisan pos:audit-product-codes
  php artisan pos:audit-product-codes --write   # backfills safe values + resolves conflicts
  php artisan migrate
  ```
- [ ] Confirm `php artisan migrate:status` shows both migrations as **Ran**.

---

## 2. Assets

- [ ] Run `npm run prod` and confirm `public/js/pos_scanner.js` and `public/js/html5-qrcode.min.js` are served with correct cache-busting versions.
- [ ] Confirm `public/js/vendor.js` contains the `onScan` library (hardware scanner support).

---

## 3. HTTPS (required for camera scanning)

Camera access (`getUserMedia`) is blocked by browsers on non-secure origins.

- [ ] `APP_URL` in `.env` must start with `https://` — the `AppServiceProvider` reads this to call `URL::forceScheme('https')`.
- [ ] Verify the load balancer / reverse proxy forwards `X-Forwarded-Proto: https` if TLS is terminated upstream.
- [ ] Test on the target device: open POS, tap the camera button — status should show "Requesting camera permission…", not "Camera scanning is unavailable".

---

## 4. Hardware scanner

- [ ] Attach a USB or Bluetooth barcode scanner to the test machine.
- [ ] Open POS, select a location, confirm `#scan_product_code` receives focus automatically.
- [ ] Scan a known product barcode — confirm it adds to cart with a success toast.
- [ ] Scan the same barcode again — confirm the quantity increments (not a duplicate row).
- [ ] Scan an unknown code — confirm the error toast "No product matched that scan." appears and focus returns to the scan input.
- [ ] Open the weighing scale modal, scan — confirm the hardware scanner feeds the modal field (not the main scan input), and closes correctly.

---

## 5. Camera scan (mobile / tablet)

- [ ] Open POS on a phone or tablet over HTTPS.
- [ ] Tap the camera icon — modal opens, rear camera starts, status reads "Camera ready. Hold the code steady inside the frame."
- [ ] Scan a barcode or QR — product is added, modal closes, focus returns to main scan input.
- [ ] Deny camera permission — status reads "Camera access failed: …" with the browser reason.
- [ ] Test on both iOS Safari and Android Chrome.

---

## 6. Document types — new print renderer

The `PosPrintRenderer` now handles all POS document output. Test one transaction for each type:

| Document | How to trigger | Check |
|---|---|---|
| Invoice (A4) | Standard sale → print | Logo, line items, totals, barcode/QR if enabled |
| Invoice (thermal 80mm) | Location with slim layout → print | Narrow layout, no grid columns, compact totals |
| Invoice (thermal 58mm) | Location with slim2 layout → print | Narrower fonts, fits 58mm roll |
| Packing slip | Sale → packing slip button | No prices, status column, total items/qty |
| Delivery note | Sale → delivery note button | Signature blocks, "Deliver To" heading, no prices |
| Quotation | Save as quotation → print | Correct heading, prices visible |
| Sale order | Sales order → print | Correct heading, prices visible |
| Purchase order | Purchase order → print (PurchaseOrderPrintRenderer path) | Supplier info, line items |

For each:
- [ ] Logo renders (if set on invoice layout)
- [ ] Business name and address correct
- [ ] Customer / party section correct
- [ ] No orphaned page breaks mid-table for A4 documents with many lines
- [ ] Barcode and QR render at correct size for the paper width

---

## 7. Barcode / QR on receipts

- [ ] Enable "Show barcode" on an invoice layout — confirm barcode renders on receipt using Code 128.
- [ ] Enable "Show QR code" — confirm QR renders at the correct size for the paper profile (120px A4, 84px 80mm, 72px 58mm).
- [ ] Test with a transaction that has a ZATCA/Saudi QR code — confirm `qr_code` image field takes precedence over generated QR.

---

## 8. Product barcode data

- [ ] Run `php artisan pos:audit-product-codes --business_id=<id>` for each active business and confirm zero duplicates reported.
- [ ] For businesses that need product-level barcodes in the new field, run with `--write` to backfill from SKU where unambiguous.

---

## 9. Cache flush

- [ ] `php artisan cache:clear`
- [ ] `php artisan config:cache`
- [ ] `php artisan view:clear`
