<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments') && ! Schema::hasColumn('departments', 'require_hod_approval_for_ai_questions')) {
            Schema::table('departments', function (Blueprint $table): void {
                $table->boolean('require_hod_approval_for_ai_questions')->default(false)->after('name');
            });
        }

        if (Schema::hasTable('app_settings')) {
            $row = DB::table('app_settings')->where('key', 'require_admin_approval_for_ai_questions')->first();
            if ($row !== null) {
                $v = strtolower((string) ($row->value ?? '0'));
                if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
                    DB::table('departments')->update(['require_hod_approval_for_ai_questions' => true]);
                }
                DB::table('app_settings')->where('key', 'require_admin_approval_for_ai_questions')->delete();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'require_hod_approval_for_ai_questions')) {
            Schema::table('departments', function (Blueprint $table): void {
                $table->dropColumn('require_hod_approval_for_ai_questions');
            });
        }
    }
};
