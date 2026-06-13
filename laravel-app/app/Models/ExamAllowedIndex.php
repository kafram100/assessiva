<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAllowedIndex extends Model
{
    protected $table = 'exam_allowed_indexes';

    /**
     * @var list<string>
     */
    protected $fillable = ['exam_id', 'student_index'];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public static function normalize(string $index): string
    {
        return strtoupper(trim($index));
    }

    /**
     * @return array<int, string>
     */
    public static function parseList(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            foreach (str_getcsv($line) as $cell) {
                $n = self::normalize((string) $cell);
                if ($n !== '') {
                    $out[$n] = $n;
                }
            }
        }

        return array_values($out);
    }
}
