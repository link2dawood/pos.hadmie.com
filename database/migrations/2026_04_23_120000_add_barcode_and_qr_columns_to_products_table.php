<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('barcode_type');
            }

            if (! Schema::hasColumn('products', 'qr_code_value')) {
                $table->string('qr_code_value')->nullable()->after('barcode');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'barcode'], 'products_business_barcode_idx');
            $table->index(['business_id', 'qr_code_value'], 'products_business_qr_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_business_barcode_idx');
            $table->dropIndex('products_business_qr_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'qr_code_value')) {
                $table->dropColumn('qr_code_value');
            }

            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }
};
