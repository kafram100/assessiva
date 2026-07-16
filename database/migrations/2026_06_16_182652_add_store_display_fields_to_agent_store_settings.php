<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_store_settings', function (Blueprint $table) {
            $table->string('accent_color', 7)->nullable()->after('theme_color');
            $table->string('hero_headline', 200)->nullable()->after('logo_url');
            $table->string('announcement_text', 300)->nullable()->after('hero_headline');
            $table->string('footer_tagline', 200)->nullable()->after('announcement_text');
        });
    }

    public function down(): void
    {
        Schema::table('agent_store_settings', function (Blueprint $table) {
            $table->dropColumn(['accent_color', 'hero_headline', 'announcement_text', 'footer_tagline']);
        });
    }
};
