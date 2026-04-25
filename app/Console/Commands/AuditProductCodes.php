<?php

namespace App\Console\Commands;

use App\Product;
use Illuminate\Console\Command;

class AuditProductCodes extends Command
{
    protected $signature = 'pos:audit-product-codes {--business_id=} {--write : Backfill safe barcode and QR values}';

    protected $description = 'Audit legacy product barcode and QR values, report duplicates, and optionally backfill safe missing values.';

    public function handle(): int
    {
        $query = Product::query()->orderBy('business_id')->orderBy('id');
        if ($this->option('business_id')) {
            $query->where('business_id', $this->option('business_id'));
        }

        $products = $query->get(['id', 'business_id', 'name', 'sku', 'barcode', 'qr_code_value']);
        if ($products->isEmpty()) {
            $this->info('No products found for the selected scope.');

            return self::SUCCESS;
        }

        $barcodeDuplicates = [];
        $qrDuplicates = [];
        $skuCandidates = [];
        $existingBarcodeCounts = [];
        $existingQrCounts = [];

        foreach ($products as $product) {
            $businessId = (int) $product->business_id;
            $sku = $this->normalize($product->sku);
            $barcode = $this->normalize($product->barcode);
            $qr = $this->normalize($product->qr_code_value);

            if ($sku !== null) {
                $skuCandidates[$businessId][$sku] = ($skuCandidates[$businessId][$sku] ?? 0) + 1;
            }
            if ($barcode !== null) {
                $existingBarcodeCounts[$businessId][$barcode] = ($existingBarcodeCounts[$businessId][$barcode] ?? 0) + 1;
            }
            if ($qr !== null) {
                $existingQrCounts[$businessId][$qr] = ($existingQrCounts[$businessId][$qr] ?? 0) + 1;
            }
        }

        foreach ($existingBarcodeCounts as $businessId => $values) {
            foreach ($values as $value => $count) {
                if ($count > 1) {
                    $barcodeDuplicates[] = [$businessId, $value, $count];
                }
            }
        }

        foreach ($existingQrCounts as $businessId => $values) {
            foreach ($values as $value => $count) {
                if ($count > 1) {
                    $qrDuplicates[] = [$businessId, $value, $count];
                }
            }
        }

        $backfilledBarcode = 0;
        $backfilledQr = 0;
        $skippedBarcode = 0;
        $skippedQr = 0;

        foreach ($products as $product) {
            $businessId = (int) $product->business_id;
            $sku = $this->normalize($product->sku);
            $barcode = $this->normalize($product->barcode);
            $qr = $this->normalize($product->qr_code_value);
            $dirty = false;

            if ($barcode === null && $sku !== null) {
                $safeForBarcode =
                    ($skuCandidates[$businessId][$sku] ?? 0) === 1 &&
                    empty($existingBarcodeCounts[$businessId][$sku]) &&
                    empty($existingQrCounts[$businessId][$sku]);

                if ($safeForBarcode) {
                    if ($this->option('write')) {
                        $product->barcode = $sku;
                    }
                    $existingBarcodeCounts[$businessId][$sku] = 1;
                    $barcode = $sku;
                    $backfilledBarcode++;
                    $dirty = true;
                } else {
                    $skippedBarcode++;
                }
            }

            if ($qr === null && $barcode !== null) {
                $safeForQr = empty($existingQrCounts[$businessId][$barcode]);
                if ($safeForQr) {
                    if ($this->option('write')) {
                        $product->qr_code_value = $barcode;
                    }
                    $existingQrCounts[$businessId][$barcode] = 1;
                    $backfilledQr++;
                    $dirty = true;
                } else {
                    $skippedQr++;
                }
            }

            if ($dirty && $this->option('write')) {
                $product->save();
            }
        }

        $this->info('Product code audit complete.');
        $this->line('Products checked: '.$products->count());
        $this->line('Duplicate barcode values: '.count($barcodeDuplicates));
        $this->line('Duplicate QR values: '.count($qrDuplicates));
        $this->line('Backfilled barcodes: '.$backfilledBarcode);
        $this->line('Backfilled QR values: '.$backfilledQr);
        $this->line('Skipped barcode backfills: '.$skippedBarcode);
        $this->line('Skipped QR backfills: '.$skippedQr);

        if (! empty($barcodeDuplicates)) {
            $this->warn('Duplicate barcode values detected:');
            $this->table(['Business', 'Barcode', 'Count'], $barcodeDuplicates);
        }

        if (! empty($qrDuplicates)) {
            $this->warn('Duplicate QR values detected:');
            $this->table(['Business', 'QR Value', 'Count'], $qrDuplicates);
        }

        if (! $this->option('write')) {
            $this->comment('Run again with --write to backfill only the clearly safe missing values.');
        }

        return self::SUCCESS;
    }

    protected function normalize($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
