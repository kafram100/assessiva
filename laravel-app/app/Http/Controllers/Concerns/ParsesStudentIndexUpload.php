<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ExamAllowedIndex;
use Illuminate\Http\Request;

trait ParsesStudentIndexUpload
{
    /**
     * @return array<int, string>
     */
    protected function normalizedIndexesFromRequest(Request $request): array
    {
        $chunks = [];
        $text = trim((string) $request->input('indexes_text', ''));
        if ($text !== '') {
            $chunks[] = $text;
        }
        if ($request->hasFile('indexes_file')) {
            $file = $request->file('indexes_file');
            $path = $file->getRealPath();
            if ($path !== false && is_readable($path)) {
                $chunks[] = (string) file_get_contents($path);
            }
        }

        return ExamAllowedIndex::parseList(implode("\n", $chunks));
    }
}
