<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('promo_codes', 'type')) {
                $table->string('type', 32)->default('bundle_free')->after('code');
            }
            if (! Schema::hasColumn('promo_codes', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('category_slug')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('promo_codes', 'min_order_amount')) {
                $table->decimal('min_order_amount', 10, 2)->nullable()->after('free_quantity');
            }
            if (! Schema::hasColumn('promo_codes', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->nullable()->after('min_order_amount');
            }
        });

        if (Schema::hasColumn('promo_codes', 'category_slug')) {
            DB::statement('ALTER TABLE promo_codes MODIFY category_slug VARCHAR(64) NULL');
        }

        DB::table('promo_codes')->whereNull('type')->orWhere('type', '')->update(['type' => 'bundle_free']);
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            if (Schema::hasColumn('promo_codes', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
            if (Schema::hasColumn('promo_codes', 'min_order_amount')) {
                $table->dropColumn('min_order_amount');
            }
            if (Schema::hasColumn('promo_codes', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
            if (Schema::hasColumn('promo_codes', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
