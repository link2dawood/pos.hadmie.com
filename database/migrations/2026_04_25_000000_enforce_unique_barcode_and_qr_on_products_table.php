<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Abort if any per-business barcode duplicates exist so the migration
        // fails loudly instead of silently dropping data.
        $barcodeDuplicates = DB::table('products')
            ->select('business_id', 'barcode', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->groupBy('business_id', 'barcode')
            ->having('cnt', '>', 1)
            ->get();

        if ($barcodeDuplicates->isNotEmpty()) {
            $rows = $barcodeDuplicates->map(fn ($r) => "business {$r->business_id}: \"{$r->barcode}\" ({$r->cnt}×)")->implode(', ');
            throw new RuntimeException("Cannot enforce barcode uniqueness — duplicate values found: {$rows}. Run `php artisan pos:audit-product-codes --write` first.");
        }

        $qrDuplicates = DB::table('products')
            ->select('business_id', 'qr_code_value', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('qr_code_value')
            ->where('qr_code_value', '!=', '')
            ->groupBy('business_id', 'qr_code_value')
            ->having('cnt', '>', 1)
            ->get();

        if ($qrDuplicates->isNotEmpty()) {
            $rows = $qrDuplicates->map(fn ($r) => "business {$r->business_id}: \"{$r->qr_code_value}\" ({$r->cnt}×)")->implode(', ');
            throw new RuntimeException("Cannot enforce QR uniqueness — duplicate values found: {$rows}. Run `php artisan pos:audit-product-codes --write` first.");
        }

        Schema::table('products', function (Blueprint $table) {
            // Replace the plain indexes added in the previous migration with unique ones.
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_keys($sm->listTableIndexes('products'));

            if (in_array('products_business_barcode_idx', $indexes, true)) {
                $table->dropIndex('products_business_barcode_idx');
            }
            if (in_array('products_business_qr_idx', $indexes, true)) {
                $table->dropIndex('products_business_qr_idx');
            }

            $table->unique(['business_id', 'barcode'], 'products_business_barcode_unique');
            $table->unique(['business_id', 'qr_code_value'], 'products_business_qr_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_business_barcode_unique');
            $table->dropUnique('products_business_qr_unique');
            $table->index(['business_id', 'barcode'], 'products_business_barcode_idx');
            $table->index(['business_id', 'qr_code_value'], 'products_business_qr_idx');
        });
    }
};
