<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasColumn('users', 'department_id')) {
            return;
        }

        if ($this->usersDepartmentForeignExists()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'department_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
    }

    private function usersDepartmentForeignExists(): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $r = DB::selectOne(
                'select 1 as x from information_schema.table_constraints
                 where constraint_schema = current_schema()
                 and table_name = ? and constraint_name = ? limit 1',
                ['users', 'users_department_id_foreign']
            );

            return $r !== null;
        }

        if ($driver === 'mysql') {
            $r = DB::selectOne(
                'select 1 as x from information_schema.table_constraints
                 where table_schema = database() and table_name = ? and constraint_name = ? limit 1',
                ['users', 'users_department_id_foreign']
            );

            return $r !== null;
        }

        return false;
    }
};
