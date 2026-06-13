<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (! Schema::hasColumn('answers', 'ai_marks_awarded')) {
                $table->decimal('ai_marks_awarded', 10, 2)->nullable()->after('marks_awarded');
            }
            if (! Schema::hasColumn('answers', 'ai_confidence')) {
                $table->decimal('ai_confidence', 5, 4)->nullable()->after('ai_marks_awarded');
            }
            if (! Schema::hasColumn('answers', 'ai_evaluation')) {
                $table->text('ai_evaluation')->nullable()->after('ai_confidence');
            }
            if (! Schema::hasColumn('answers', 'detected_concepts')) {
                $table->json('detected_concepts')->nullable()->after('ai_evaluation');
            }
            if (! Schema::hasColumn('answers', 'missing_concepts')) {
                $table->json('missing_concepts')->nullable()->after('detected_concepts');
            }
            if (! Schema::hasColumn('answers', 'evaluation_reason')) {
                $table->text('evaluation_reason')->nullable()->after('missing_concepts');
            }
            if (! Schema::hasColumn('answers', 'evaluation_status')) {
                $table->string('evaluation_status', 32)->nullable()->after('evaluation_reason');
            }
            if (! Schema::hasColumn('answers', 'expected_answer_snapshot')) {
                $table->text('expected_answer_snapshot')->nullable()->after('evaluation_status');
            }
            if (! Schema::hasColumn('answers', 'lecturer_grade_comment')) {
                $table->text('lecturer_grade_comment')->nullable()->after('expected_answer_snapshot');
            }
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->decimal('marks_awarded', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            foreach ([
                'lecturer_grade_comment',
                'expected_answer_snapshot',
                'evaluation_status',
                'evaluation_reason',
                'missing_concepts',
                'detected_concepts',
                'ai_evaluation',
                'ai_confidence',
                'ai_marks_awarded',
            ] as $col) {
                if (Schema::hasColumn('answers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->integer('marks_awarded')->nullable()->change();
        });
    }
};
