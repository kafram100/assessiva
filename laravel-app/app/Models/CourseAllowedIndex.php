<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAllowedIndex extends Model
{
    protected $table = 'course_allowed_indexes';

    /**
     * @var list<string>
     */
    protected $fillable = ['course_id', 'student_index'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public static function normalize(string $index): string
    {
        return ExamAllowedIndex::normalize($index);
    }

    /**
     * @return array<int, string>
     */
    public static function parseList(string $raw): array
    {
        return ExamAllowedIndex::parseList($raw);
    }
}
