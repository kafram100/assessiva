<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_packages', function (Blueprint $table) {
            $table->id();
            $table->string('network_type', 50);
            $table->string('package_name', 100);
            $table->string('package_size', 50);
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('network_type');
            $table->index('is_active');
        });

        Schema::create('agent_packages', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20)->default('agent');
            $table->string('network_type', 50);
            $table->string('package_name', 120);
            $table->decimal('package_size_gb', 10, 4)->default(0);
            $table->integer('package_size_mb')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['role', 'network_type']);
            $table->index(['role', 'network_type', 'package_size_mb']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_packages');
        Schema::dropIfExists('customer_packages');
    }
};
