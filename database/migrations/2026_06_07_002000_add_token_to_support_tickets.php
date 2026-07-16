<?php

use App\Models\SupportTicket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('token', 64)->after('id')->nullable()->unique();
        });

        // backfill tokens for existing tickets
        SupportTicket::whereNull('token')->chunkById(100, function ($tickets) {
            foreach ($tickets as $ticket) {
                $ticket->token = Str::random(64);
                $ticket->save();
            }
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('token', 64)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
