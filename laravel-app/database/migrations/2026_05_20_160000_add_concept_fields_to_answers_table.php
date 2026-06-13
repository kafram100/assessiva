<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (! Schema::hasColumn('answers', 'ai_concept_coverage')) {
                $table->decimal('ai_concept_coverage', 5, 4)->nullable()->after('ai_confidence');
            }
            if (! Schema::hasColumn('answers', 'rubric_concepts')) {
                $table->json('rubric_concepts')->nullable()->after('missing_concepts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            foreach (['rubric_concepts', 'ai_concept_coverage'] as $col) {
                if (Schema::hasColumn('answers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
