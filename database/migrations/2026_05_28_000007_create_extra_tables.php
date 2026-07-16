<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_config', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique();
            $table->text('config_value')->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name', 100)->unique();
            $table->string('subject', 255);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email', 255);
            $table->string('subject', 255);
            $table->text('body')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index('recipient_email');
            $table->index('status');
        });

        Schema::create('sms_config', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique();
            $table->text('config_value')->nullable();
            $table->string('api_url', 500)->nullable();
            $table->string('api_key', 255)->nullable();
            $table->string('sender_name', 50)->nullable();
            $table->string('callback_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 50);
            $table->text('message');
            $table->string('type', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('message_id', 100)->nullable();
            $table->string('delivery_status', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index('phone_number');
            $table->index('status');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_config');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_config');
    }
};
