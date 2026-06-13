<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sync_queue')) {
            Schema::create('sync_queue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('operation_id', 36)->unique();
                $table->string('operation_type', 50);
                $table->string('table_name', 50);
                $table->integer('record_id')->nullable();
                $table->json('data')->nullable();
                $table->enum('status', ['pending', 'syncing', 'completed', 'failed'])->default('pending');
                $table->integer('retry_count')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('synced_at')->nullable();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('sync_metadata')) {
            Schema::create('sync_metadata', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->timestamp('last_sync_at')->nullable();
                $table->string('device_id', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_metadata');
        Schema::dropIfExists('sync_queue');
    }
};
