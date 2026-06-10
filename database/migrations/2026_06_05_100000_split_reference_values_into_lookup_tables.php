<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const TYPE_TO_TABLE = [
        'stock_status' => 'stock_statuses',
        'care_difficulty' => 'care_difficulties',
        'product_size' => 'product_sizes',
        'age_group' => 'age_groups',
        'order_status' => 'order_statuses',
        'payment_method' => 'payment_methods',
        'payment_status' => 'payment_statuses',
        'user_role' => 'user_roles',
        'promo_type' => 'promo_types',
    ];

    /** @var array<string, array{table: string, column: string}> */
    private const REMAP_COLUMNS = [
        'stock_status' => ['table' => 'products', 'column' => 'stock_status_id'],
        'care_difficulty' => ['table' => 'products', 'column' => 'care_difficulty_id'],
        'product_size' => ['table' => 'products', 'column' => 'product_size_id'],
        'age_group' => ['table' => 'products', 'column' => 'age_group_id'],
        'order_status' => ['table' => 'orders', 'column' => 'order_status_id'],
        'payment_method' => ['table' => 'orders', 'column' => 'payment_method_id'],
        'payment_status' => ['table' => 'orders', 'column' => 'payment_status_id'],
        'user_role' => ['table' => 'users', 'column' => 'role_id'],
        'promo_type' => ['table' => 'promo_codes', 'column' => 'promo_type_id'],
    ];

    public function up(): void
    {
        foreach (self::TYPE_TO_TABLE as $table) {
            $this->createLookupTable($table);
        }

        if (! Schema::hasTable('reference_values')) {
            $this->seedDefaultLookups();

            return;
        }

        $maps = [];
        foreach (self::TYPE_TO_TABLE as $type => $table) {
            $maps[$type] = $this->importFromReferenceValues($type, $table);
        }

        $this->dropReferenceForeignKeys();
        $this->remapForeignIds($maps);
        $this->addLookupForeignKeys();
        Schema::dropIfExists('reference_values');
    }

    public function down(): void
    {
        // Только migrate:fresh на dev.
    }

    private function createLookupTable(string $table): void
    {
        if (Schema::hasTable($table)) {
            $this->ensureLookupColumns($table);

            return;
        }

        Schema::create($table, function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('code', 32)->unique();
            $blueprint->string('label', 64);
            $blueprint->string('description', 255)->nullable();
            $blueprint->unsignedSmallInteger('sort_order')->default(0);
            $blueprint->boolean('is_active')->default(true);
        });
    }

    private function ensureLookupColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'description')) {
                $blueprint->string('description', 255)->nullable()->after('label');
            }
            if (! Schema::hasColumn($table, 'sort_order')) {
                $blueprint->unsignedSmallInteger('sort_order')->default(0)->after('description');
            }
            if (! Schema::hasColumn($table, 'is_active')) {
                $blueprint->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    /**
     * @return array<int, int> old reference_values.id => new lookup id
     */
    private function importFromReferenceValues(string $type, string $table): array
    {
        $map = [];

        foreach (DB::table('reference_values')->where('type', $type)->orderBy('sort_order')->orderBy('id')->get() as $row) {
            $payload = [
                'label' => $row->label,
                'description' => $row->description ?? null,
                'sort_order' => (int) ($row->sort_order ?? 0),
                'is_active' => (bool) ($row->is_active ?? true),
            ];

            $existingId = DB::table($table)->where('code', $row->code)->value('id');

            if ($existingId) {
                DB::table($table)->where('id', $existingId)->update($payload);
                $newId = (int) $existingId;
            } else {
                $newId = (int) DB::table($table)->insertGetId(array_merge(
                    ['code' => $row->code],
                    $payload
                ));
            }

            $map[(int) $row->id] = $newId;
        }

        return $map;
    }

    private function seedDefaultLookups(): void
    {
        $this->seedRows('stock_statuses', [
            ['code' => 'in_stock', 'label' => 'В наличии'],
            ['code' => 'out_of_stock', 'label' => 'Закончился'],
        ]);
        $this->seedRows('care_difficulties', [
            ['code' => 'easy', 'label' => 'Лёгкий'],
            ['code' => 'medium', 'label' => 'Средний'],
            ['code' => 'hard', 'label' => 'Сложный'],
        ]);
        $this->seedRows('product_sizes', [
            ['code' => 'small', 'label' => 'Маленький'],
            ['code' => 'medium', 'label' => 'Средний'],
            ['code' => 'large', 'label' => 'Большой'],
            ['code' => 'extra_large', 'label' => 'Очень большой'],
        ]);
        $this->seedRows('age_groups', [
            ['code' => 'young', 'label' => 'Молодое'],
            ['code' => 'mature', 'label' => 'Взрослое'],
            ['code' => 'old', 'label' => 'Старое'],
        ]);
        $this->seedRows('order_statuses', [
            ['code' => 'processing', 'label' => 'в обработке'],
            ['code' => 'awaiting_payment', 'label' => 'ожидает оплаты'],
            ['code' => 'shipped', 'label' => 'отправлен'],
            ['code' => 'completed', 'label' => 'выполнен'],
            ['code' => 'cancelled', 'label' => 'отменен'],
        ]);
        $this->seedRows('payment_methods', [
            ['code' => 'cash', 'label' => 'Наличные'],
            ['code' => 'card_courier', 'label' => 'Картой курьеру'],
            ['code' => 'yookassa', 'label' => 'ЮKassa (онлайн)'],
        ]);
        $this->seedRows('payment_statuses', [
            ['code' => 'unpaid', 'label' => 'Не оплачен'],
            ['code' => 'pending', 'label' => 'Ожидает оплаты'],
            ['code' => 'paid', 'label' => 'Оплачен'],
            ['code' => 'failed', 'label' => 'Ошибка оплаты'],
            ['code' => 'canceled', 'label' => 'Оплата отменена'],
        ]);
        $this->seedRows('user_roles', [
            ['code' => 'user', 'label' => 'Пользователь'],
            ['code' => 'admin', 'label' => 'Администратор'],
        ]);
        $this->seedRows('promo_types', [
            ['code' => 'bundle_free', 'label' => 'N+1 бесплатно'],
            ['code' => 'order_percent', 'label' => 'Скидка % от заказа'],
        ]);

        $this->addLookupForeignKeys();
    }

    /**
     * @param  list<array{code: string, label: string}>  $rows
     */
    private function seedRows(string $table, array $rows): void
    {
        foreach ($rows as $index => $row) {
            DB::table($table)->updateOrInsert(
                ['code' => $row['code']],
                [
                    'label' => $row['label'],
                    'description' => $row['label'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @param  array<string, array<int, int>>  $maps
     */
    private function remapForeignIds(array $maps): void
    {
        foreach (self::REMAP_COLUMNS as $type => ['table' => $table, 'column' => $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $map = $maps[$type] ?? [];
            if ($map === []) {
                continue;
            }

            foreach (DB::table($table)->whereNotNull($column)->select('id', $column)->get() as $row) {
                $oldId = (int) $row->{$column};
                if (isset($map[$oldId])) {
                    DB::table($table)->where('id', $row->id)->update([$column => $map[$oldId]]);
                }
            }
        }
    }

    private function dropReferenceForeignKeys(): void
    {
        $definitions = [
            ['products', ['stock_status_id', 'care_difficulty_id', 'product_size_id', 'age_group_id']],
            ['orders', ['order_status_id', 'payment_method_id', 'payment_status_id']],
            ['users', ['role_id']],
            ['promo_codes', ['promo_type_id']],
        ];

        foreach ($definitions as [$table, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        continue;
                    }
                    try {
                        $blueprint->dropForeign([$column]);
                    } catch (\Throwable) {
                        //
                    }
                }
            });
        }
    }

    private function addLookupForeignKeys(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->addFk('products', $table, 'stock_status_id', 'stock_statuses');
                $this->addFk('products', $table, 'care_difficulty_id', 'care_difficulties');
                $this->addFk('products', $table, 'product_size_id', 'product_sizes');
                $this->addFk('products', $table, 'age_group_id', 'age_groups');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $this->addFk('orders', $table, 'order_status_id', 'order_statuses');
                $this->addFk('orders', $table, 'payment_method_id', 'payment_methods');
                $this->addFk('orders', $table, 'payment_status_id', 'payment_statuses');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $this->addFk('users', $table, 'role_id', 'user_roles');
            });
        }

        if (Schema::hasTable('promo_codes') && Schema::hasColumn('promo_codes', 'promo_type_id')) {
            Schema::table('promo_codes', function (Blueprint $table) {
                $this->addFk('promo_codes', $table, 'promo_type_id', 'promo_types');
            });
        }
    }

    private function addFk(string $ownerTable, Blueprint $table, string $column, string $referencedTable): void
    {
        if (! Schema::hasColumn($ownerTable, $column)) {
            return;
        }

        try {
            $table->foreign($column)->references('id')->on($referencedTable)->nullOnDelete();
        } catch (\Throwable) {
            //
        }
    }
};
