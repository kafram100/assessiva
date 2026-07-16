<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_tickets', 'phone')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_tickets', 'phone')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
