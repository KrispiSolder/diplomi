<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('reference_values')) {
            return;
        }

        foreach ([
            ['code' => 'user', 'label' => 'Пользователь'],
            ['code' => 'admin', 'label' => 'Администратор'],
        ] as $row) {
            DB::table('reference_values')->updateOrInsert(
                ['type' => 'user_role', 'code' => $row['code']],
                ['label' => $row['label']]
            );
        }

        $userRoleId = DB::table('reference_values')
            ->where('type', 'user_role')
            ->where('code', 'user')
            ->value('id');

        $adminRoleId = DB::table('reference_values')
            ->where('type', 'user_role')
            ->where('code', 'admin')
            ->value('id');

        if (! $userRoleId || ! $adminRoleId) {
            return;
        }

        if (! Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')
                    ->nullable()
                    ->after('password')
                    ->constrained('reference_values')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            foreach (DB::table('users')->select('id', 'role')->get() as $row) {
                $isAdmin = in_array((string) $row->role, ['admin', 'administrator'], true);
                DB::table('users')->where('id', $row->id)->update([
                    'role_id' => $isAdmin ? $adminRoleId : $userRoleId,
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        DB::table('users')->whereNull('role_id')->update(['role_id' => $userRoleId]);
    }

    public function down(): void
    {
        // Не восстанавливаем ENUM role.
    }
};
