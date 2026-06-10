<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_LOOKUPS = [
        'stock_statuses' => 'stock_status',
        'care_difficulties' => 'care_difficulty',
        'product_sizes' => 'product_size',
        'age_groups' => 'age_group',
        'order_statuses' => 'order_status',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('reference_values')) {
            Schema::create('reference_values', function (Blueprint $table) {
                $table->id();
                $table->string('type', 32);
                $table->string('code', 32);
                $table->string('label', 64);
                $table->unique(['type', 'code']);
            });
        }

        foreach (self::LEGACY_LOOKUPS as $legacyTable => $type) {
            $this->importLegacyTable($legacyTable, $type);
        }

        $this->seedPaymentStatuses();
        $this->dropProductLookupForeignKeys();
        $this->remapProductForeignKeys();
        $this->dropOrderLookupForeignKeys();
        $this->remapOrderStatusIds();
        $this->addReferenceForeignKeys();

        if (! Schema::hasColumn('orders', 'payment_status_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('payment_status_id')->nullable()->after('order_status_id');
            });
        }

        $this->remapOrderPaymentStatus();

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });

        foreach (array_keys(self::LEGACY_LOOKUPS) as $legacy) {
            Schema::dropIfExists($legacy);
        }
    }

    public function down(): void
    {
        // Только migrate:fresh на dev.
    }

    private function importLegacyTable(string $legacyTable, string $type): void
    {
        if (! Schema::hasTable($legacyTable)) {
            return;
        }

        foreach (DB::table($legacyTable)->get() as $row) {
            DB::table('reference_values')->updateOrInsert(
                ['type' => $type, 'code' => $row->code],
                ['label' => $row->label]
            );
        }
    }

    private function seedPaymentStatuses(): void
    {
        foreach ([
            ['code' => 'pending', 'label' => 'pending'],
            ['code' => 'paid', 'label' => 'paid'],
            ['code' => 'failed', 'label' => 'failed'],
        ] as $row) {
            DB::table('reference_values')->updateOrInsert(
                ['type' => 'payment_status', 'code' => $row['code']],
                ['label' => $row['label']]
            );
        }
    }

    private function dropProductLookupForeignKeys(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            foreach (['stock_status_id', 'care_difficulty_id', 'product_size_id', 'age_group_id'] as $column) {
                if (! Schema::hasColumn('products', $column)) {
                    continue;
                }
                try {
                    $table->dropForeign([$column]);
                } catch (\Throwable) {
                    // FK уже снят
                }
            }
        });
    }

    private function remapProductForeignKeys(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $legacyByColumn = [
            'stock_status_id' => ['stock_statuses', 'stock_status'],
            'care_difficulty_id' => ['care_difficulties', 'care_difficulty'],
            'product_size_id' => ['product_sizes', 'product_size'],
            'age_group_id' => ['age_groups', 'age_group'],
        ];

        foreach (DB::table('products')->get() as $product) {
            $updates = [];
            foreach ($legacyByColumn as $column => [$legacyTable, $type]) {
                $oldId = $product->{$column} ?? null;
                if (! $oldId || ! Schema::hasTable($legacyTable)) {
                    continue;
                }
                $legacy = DB::table($legacyTable)->where('id', $oldId)->first();
                if (! $legacy) {
                    continue;
                }
                $newId = DB::table('reference_values')
                    ->where('type', $type)
                    ->where('code', $legacy->code)
                    ->value('id');
                if ($newId) {
                    $updates[$column] = $newId;
                }
            }
            if ($updates !== []) {
                DB::table('products')->where('id', $product->id)->update($updates);
            }
        }
    }

    private function dropOrderLookupForeignKeys(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_status_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropForeign(['order_status_id']);
            } catch (\Throwable) {
                //
            }
        });
    }

    private function remapOrderStatusIds(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_status_id')) {
            return;
        }

        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        foreach (DB::table('orders')->get() as $order) {
            if (! $order->order_status_id) {
                continue;
            }
            $legacy = DB::table('order_statuses')->where('id', $order->order_status_id)->first();
            if (! $legacy) {
                continue;
            }
            $newId = DB::table('reference_values')
                ->where('type', 'order_status')
                ->where('code', $legacy->code)
                ->value('id');
            if ($newId) {
                DB::table('orders')->where('id', $order->id)->update(['order_status_id' => $newId]);
            }
        }
    }

    private function addReferenceForeignKeys(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                foreach (['stock_status_id', 'care_difficulty_id', 'product_size_id', 'age_group_id'] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $table->foreign($column)->references('id')->on('reference_values')->nullOnDelete();
                    }
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'order_status_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('order_status_id')->references('id')->on('reference_values')->nullOnDelete();
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_status_id')) {
            Schema::table('orders', function (Blueprint $table) {
                try {
                    $table->foreign('payment_status_id')->references('id')->on('reference_values')->nullOnDelete();
                } catch (\Throwable) {
                    //
                }
            });
        }
    }

    private function remapOrderPaymentStatus(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'payment_status')) {
            return;
        }

        foreach (DB::table('orders')->get() as $order) {
            $code = $order->payment_status;
            if ($code === null || $code === '') {
                continue;
            }
            $newId = DB::table('reference_values')
                ->where('type', 'payment_status')
                ->where('code', $code)
                ->value('id');
            if ($newId) {
                DB::table('orders')->where('id', $order->id)->update(['payment_status_id' => $newId]);
            }
        }
    }
};
