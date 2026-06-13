<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proctoring_penalty_rules')) {
            Schema::create('proctoring_penalty_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id');
                $table->string('event_type', 80);
                $table->decimal('penalty_marks', 10, 2)->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->string('severity', 32)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->unique(['exam_id', 'event_type']);
                $table->index('exam_id');
            });
        }

        Schema::table('cheat_events', function (Blueprint $table) {
            if (! Schema::hasColumn('cheat_events', 'penalty_marks')) {
                $table->decimal('penalty_marks', 10, 2)->nullable()->after('severity');
            }
            if (! Schema::hasColumn('cheat_events', 'penalty_applied')) {
                $table->boolean('penalty_applied')->default(false)->after('penalty_marks');
            }
            if (! Schema::hasColumn('cheat_events', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('penalty_applied');
            }
            if (! Schema::hasColumn('cheat_events', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('cheat_events', 'review_status')) {
                $table->string('review_status', 24)->default('pending')->after('reviewed_at');
            }
        });

        if (Schema::hasColumn('cheat_events', 'reviewed_by')) {
            try {
                Schema::table('cheat_events', function (Blueprint $table) {
                    $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Throwable) {
                // constraint may already exist
            }
        }

        Schema::table('attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('attempts', 'raw_score')) {
                $table->decimal('raw_score', 10, 2)->nullable()->after('score');
            }
            if (! Schema::hasColumn('attempts', 'proctoring_penalty_total')) {
                $table->decimal('proctoring_penalty_total', 10, 2)->default(0)->after('raw_score');
            }
        });

        DB::table('attempts')->whereNull('raw_score')->update([
            'raw_score' => DB::raw('score'),
        ]);

        DB::table('attempts')->whereNull('proctoring_penalty_total')->update([
            'proctoring_penalty_total' => 0,
        ]);

        if (Schema::hasTable('exam_cheating_penalties') && Schema::hasTable('proctoring_penalty_rules')) {
            $rows = DB::table('exam_cheating_penalties')->get();
            foreach ($rows as $row) {
                $exists = DB::table('proctoring_penalty_rules')
                    ->where('exam_id', $row->exam_id)
                    ->where('event_type', $row->event_type)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('proctoring_penalty_rules')->insert([
                    'exam_id' => $row->exam_id,
                    'event_type' => $row->event_type,
                    'penalty_marks' => abs((float) $row->deduction_points),
                    'is_enabled' => (bool) $row->is_enabled,
                    'severity' => null,
                    'description' => null,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proctoring_penalty_rules');

        Schema::table('cheat_events', function (Blueprint $table) {
            if (Schema::hasColumn('cheat_events', 'reviewed_by')) {
                try {
                    $table->dropForeign(['reviewed_by']);
                } catch (\Throwable) {
                    //
                }
            }
        });

        $cheatExtra = array_values(array_filter(
            ['review_status', 'reviewed_at', 'reviewed_by', 'penalty_applied', 'penalty_marks'],
            fn (string $c) => Schema::hasColumn('cheat_events', $c)
        ));
        if ($cheatExtra !== []) {
            Schema::table('cheat_events', fn (Blueprint $table) => $table->dropColumn($cheatExtra));
        }

        $attemptExtra = array_values(array_filter(
            ['proctoring_penalty_total', 'raw_score'],
            fn (string $c) => Schema::hasColumn('attempts', $c)
        ));
        if ($attemptExtra !== []) {
            Schema::table('attempts', fn (Blueprint $table) => $table->dropColumn($attemptExtra));
        }
    }
};
