<?php

use App\Models\Attempt;
use App\Models\ExamSecurityLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table): void {
            $table->timestamp('start_time')->nullable()->change();
        });

        // Fix legacy ongoing attempts: clock should start at first pre_start verification, not attempt row insert.
        Attempt::query()
            ->where('status', 'ongoing')
            ->whereExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('exam_security_logs')
                    ->whereColumn('exam_security_logs.exam_attempt_id', 'attempts.id')
                    ->where('exam_security_logs.activity_type', 'pre_start');
            })
            ->orderBy('id')
            ->chunkById(100, function ($attempts): void {
                foreach ($attempts as $attempt) {
                    if (! $attempt instanceof Attempt) {
                        continue;
                    }
                    $first = ExamSecurityLog::query()
                        ->where('exam_attempt_id', $attempt->id)
                        ->where('activity_type', 'pre_start')
                        ->min('timestamp');
                    if ($first !== null) {
                        $attempt->update(['start_time' => $first]);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('attempts')->whereNull('start_time')->update(['start_time' => now()]);

        Schema::table('attempts', function (Blueprint $table): void {
            $table->timestamp('start_time')->nullable(false)->change();
        });
    }
};
