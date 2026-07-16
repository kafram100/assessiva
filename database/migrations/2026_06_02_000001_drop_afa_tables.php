<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('public_afa_registrations');
        Schema::dropIfExists('afa_registrations');
        Schema::dropIfExists('registration_fees');
    }

    public function down(): void
    {
    }
};
