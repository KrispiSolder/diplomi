<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reference_values')) {
            return;
        }

        Schema::table('reference_values', function (Blueprint $table) {
            if (! Schema::hasColumn('reference_values', 'description')) {
                $table->string('description', 255)->nullable()->after('label');
            }
            if (! Schema::hasColumn('reference_values', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('description');
            }
            if (! Schema::hasColumn('reference_values', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });

        $this->seedPromoTypes();
        $this->enrichReferenceDescriptions();
        $this->migratePromoCodesToTypeFk();
        $this->ensureReferenceForeignKeys();
    }

    public function down(): void
    {
        // Только migrate:fresh на dev.
    }

    private function seedPromoTypes(): void
    {
        foreach ([
            ['code' => 'bundle_free', 'label' => 'N+1 бесплатно', 'description' => 'Акция на категорию или товар: платные + бесплатные единицы в комплекте', 'sort_order' => 1],
            ['code' => 'order_percent', 'label' => 'Скидка % от заказа', 'description' => 'Процентная скидка на всю корзину при достижении минимальной суммы', 'sort_order' => 2],
        ] as $row) {
            DB::table('reference_values')->updateOrInsert(
                ['type' => 'promo_type', 'code' => $row['code']],
                [
                    'label' => $row['label'],
                    'description' => $row['description'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function enrichReferenceDescriptions(): void
    {
        $descriptionsByTypeCode = [
            'stock_status' => [
                'in_stock' => 'Товар доступен для заказа',
                'out_of_stock' => 'Товар временно недоступен',
            ],
            'order_status' => [
                'processing' => 'Заказ принят и обрабатывается',
                'awaiting_payment' => 'Ожидается онлайн-оплата',
                'shipped' => 'Заказ передан в доставку',
                'completed' => 'Заказ выполнен',
                'cancelled' => 'Заказ отменён',
            ],
            'payment_method' => [
                'cash' => 'Оплата наличными при получении',
                'card_courier' => 'Оплата картой курьеру',
                'yookassa' => 'Онлайн-оплата через ЮKassa',
            ],
            'payment_status' => [
                'pending' => 'Платёж не завершён',
                'paid' => 'Платёж успешно проведён',
                'failed' => 'Ошибка при оплате',
                'canceled' => 'Платёж отменён',
            ],
            'user_role' => [
                'user' => 'Обычный покупатель',
                'admin' => 'Администратор магазина',
            ],
        ];

        foreach (DB::table('reference_values')->get() as $row) {
            $description = $descriptionsByTypeCode[$row->type][$row->code] ?? $row->label;
            DB::table('reference_values')->where('id', $row->id)->update([
                'description' => $description,
            ]);
        }
    }

    private function migratePromoCodesToTypeFk(): void
    {
        if (! Schema::hasTable('promo_codes')) {
            return;
        }

        if (! Schema::hasColumn('promo_codes', 'promo_type_id')) {
            Schema::table('promo_codes', function (Blueprint $table) {
                $table->foreignId('promo_type_id')
                    ->nullable()
                    ->after('code')
                    ->constrained('reference_values')
                    ->nullOnDelete();
            });
        }

        foreach (DB::table('promo_codes')->get() as $promo) {
            $typeCode = $promo->type ?? 'bundle_free';
            $typeId = DB::table('reference_values')
                ->where('type', 'promo_type')
                ->where('code', $typeCode)
                ->value('id');

            if ($typeId) {
                DB::table('promo_codes')->where('id', $promo->id)->update(['promo_type_id' => $typeId]);
            }
        }

        $defaultTypeId = DB::table('reference_values')
            ->where('type', 'promo_type')
            ->where('code', 'bundle_free')
            ->value('id');

        if ($defaultTypeId) {
            DB::table('promo_codes')->whereNull('promo_type_id')->update(['promo_type_id' => $defaultTypeId]);
        }

        if (Schema::hasColumn('promo_codes', 'type')) {
            Schema::table('promo_codes', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    private function ensureReferenceForeignKeys(): void
    {
        if (Schema::hasTable('products')) {
            $this->addFkIfMissing('products', 'stock_status_id');
            $this->addFkIfMissing('products', 'care_difficulty_id');
            $this->addFkIfMissing('products', 'product_size_id');
            $this->addFkIfMissing('products', 'age_group_id');
        }

        if (Schema::hasTable('orders')) {
            $this->addFkIfMissing('orders', 'order_status_id');
            $this->addFkIfMissing('orders', 'payment_method_id');
            $this->addFkIfMissing('orders', 'payment_status_id');
        }

        if (Schema::hasTable('users')) {
            $this->addFkIfMissing('users', 'role_id');
        }
    }

    private function addFkIfMissing(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->foreign($column)->references('id')->on('reference_values')->nullOnDelete();
            });
        } catch (\Throwable) {
            // FK уже существует
        }
    }
};
