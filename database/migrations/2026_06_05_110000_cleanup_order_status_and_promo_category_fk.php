<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->removeLegacyPendingOrderStatus();
        $this->migratePromoCategoryToForeignKey();
    }

    public function down(): void
    {
        // Только migrate:fresh на dev.
    }

    private function removeLegacyPendingOrderStatus(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        $pendingId = DB::table('order_statuses')->where('code', 'pending')->value('id');
        if (! $pendingId) {
            return;
        }

        $inUse = Schema::hasTable('orders')
            && DB::table('orders')->where('order_status_id', $pendingId)->exists();

        if (! $inUse) {
            DB::table('order_statuses')->where('id', $pendingId)->delete();
        }
    }

    private function migratePromoCategoryToForeignKey(): void
    {
        if (! Schema::hasTable('promo_codes')) {
            return;
        }

        if (! Schema::hasColumn('promo_codes', 'category_id')) {
            Schema::table('promo_codes', function (Blueprint $table) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('promo_type_id')
                    ->constrained('categories')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('promo_codes', 'category_slug')) {
            $promos = DB::table('promo_codes')
                ->whereNotNull('category_slug')
                ->where('category_slug', '!=', '')
                ->get(['id', 'category_slug']);

            foreach ($promos as $promo) {
                $categoryId = DB::table('categories')->where('slug', $promo->category_slug)->value('id');
                if ($categoryId) {
                    DB::table('promo_codes')->where('id', $promo->id)->update(['category_id' => $categoryId]);
                }
            }

            Schema::table('promo_codes', function (Blueprint $table) {
                $table->dropColumn('category_slug');
            });
        }
    }
};
