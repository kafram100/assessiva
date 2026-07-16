<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('user_id');
            $table->string('name', 100)->nullable()->change();
            $table->string('email', 100)->nullable()->change();
            $table->text('message')->nullable()->change();
            $table->text('reply')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->string('name', 100)->nullable(false)->change();
            $table->string('email', 100)->nullable(false)->change();
            $table->text('message')->nullable(false)->change();
        });
    }
};
