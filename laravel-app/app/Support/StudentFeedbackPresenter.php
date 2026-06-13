<?php

namespace App\Support;

class StudentFeedbackPresenter
{
    /** @var array<string, list<string>> */
    private const THEME_RULES = [
        'Algorithm design' => ['algorithm', 'pseudocode', 'pseudo-code', 'logic', 'planning', 'abstraction', 'decomposition', 'flowchart', 'step-by-step', 'outline', 'pre-coding', 'simplification'],
        'Python & modularity' => ['python', 'import', 'library', 'module', 'function', 'reusable', 'package', 'syntax', 'variable'],
        'Problem solving' => ['problem', 'solution', 'approach', 'strategy', 'debug', 'trace', 'reasoning', 'clarity'],
        'Data & structures' => ['array', 'list', 'stack', 'queue', 'tree', 'graph', 'hash', 'structure', 'data'],
        'Database concepts' => ['sql', 'database', 'query', 'normalization', 'schema', 'table', 'relation'],
        'Theory & definitions' => ['definition', 'principle', 'theory', 'concept', 'term', 'explain', 'describe'],
    ];

    public static function mergeFeedbackTexts(?string $reason, ?string $evaluation): ?string
    {
        $reason = trim((string) ($reason ?? ''));
        $evaluation = trim((string) ($evaluation ?? ''));

        if ($reason === '' && $evaluation === '') {
            return null;
        }

        if ($reason === '') {
            return self::truncateText($evaluation);
        }

        if ($evaluation === '') {
            return self::truncateText($reason);
        }

        if (self::textsSimilar($reason, $evaluation)) {
            $preferred = self::pickPreferredText($reason, $evaluation);

            return self::truncateText($preferred);
        }

        if (self::addsNewInfo($reason, $evaluation)) {
            return self::truncateText(rtrim($reason, '.').'. '.$evaluation);
        }

        return self::truncateText($reason);
    }

    /**
     * @param  list<string>  $detectedConcepts
     * @param  list<string>  $missingConcepts
     */
    public static function understandingLevel(
        ?string $evaluationStatus,
        ?string $studentAnswer,
        array $detectedConcepts,
        array $missingConcepts,
        ?float $conceptCoverage,
        float $marksAwarded,
        float $maxMarks,
    ): string {
        $status = strtolower(trim((string) ($evaluationStatus ?? '')));
        $answer = trim((string) ($studentAnswer ?? ''));

        if ($status === 'ai_marking_disabled') {
            return 'Pending review';
        }

        if ($status === 'ai_unavailable') {
            return 'Feedback unavailable';
        }

        if ($answer === '') {
            return 'Unanswered';
        }

        if (preg_match('/\b(i don[\'’]?t know|dont know|no idea|not sure|idk|unsure)\b/i', $answer)) {
            return 'Insufficient response';
        }

        if ($status === 'unanswered') {
            return count($detectedConcepts) > 0 ? 'Partial understanding' : 'Off-topic response';
        }

        if ($status === 'correct') {
            return 'Strong understanding';
        }

        if ($status === 'partial') {
            return 'Partial understanding';
        }

        if ($status === 'incorrect') {
            if (count($detectedConcepts) === 0) {
                return 'Off-topic response';
            }

            return 'Partial understanding';
        }

        if ($maxMarks > 0) {
            $ratio = $marksAwarded / $maxMarks;
            if ($ratio >= 0.85) {
                return 'Strong understanding';
            }
            if ($ratio > 0) {
                return 'Partial understanding';
            }
        }

        if (count($detectedConcepts) > 0 && count($missingConcepts) > 0) {
            return 'Partial understanding';
        }

        if (count($detectedConcepts) > 0) {
            return 'Partial understanding';
        }

        return 'Off-topic response';
    }

    /**
     * @param  list<string>  $concepts
     * @return list<array{theme: string, concepts: list<string>}>
     */
    public static function groupConceptsIntoThemes(array $concepts): array
    {
        $unique = self::uniqueConcepts($concepts);
        if ($unique === []) {
            return [];
        }

        $grouped = [];
        $assigned = [];

        foreach (self::THEME_RULES as $theme => $keywords) {
            $matches = [];
            foreach ($unique as $concept) {
                if (isset($assigned[$concept])) {
                    continue;
                }
                if (self::conceptMatchesTheme($concept, $keywords)) {
                    $matches[] = $concept;
                    $assigned[$concept] = true;
                }
            }
            if ($matches !== []) {
                $grouped[] = [
                    'theme' => $theme,
                    'concepts' => $matches,
                ];
            }
        }

        $remaining = array_values(array_filter($unique, fn (string $c) => ! isset($assigned[$c])));
        if ($remaining !== []) {
            $grouped[] = [
                'theme' => 'Other focus areas',
                'concepts' => $remaining,
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<string>  $missingConcepts
     * @param  list<array{theme: string, concepts: list<string>}>  $themes
     */
    public static function revisionFocus(array $missingConcepts, array $themes, ?string $mergedFeedback): ?string
    {
        if ($themes !== []) {
            $primary = $themes[0];
            $theme = $primary['theme'];
            $concepts = $primary['concepts'];

            if ($theme === 'Algorithm design' && $concepts !== []) {
                return 'Review how pseudocode and planning steps help simplify algorithm design before coding.';
            }

            if ($theme === 'Python & modularity') {
                return 'Practice structuring solutions with clear imports, functions, and reusable code blocks.';
            }

            if (count($concepts) >= 2) {
                return sprintf(
                    'Strengthen %s by revisiting %s and %s with a short worked example.',
                    strtolower($theme),
                    $concepts[0],
                    $concepts[1],
                );
            }

            if (count($concepts) === 1) {
                return sprintf('Review how %s fits into %s for questions like this.', $concepts[0], strtolower($theme));
            }

            return sprintf('Spend time reinforcing %s with targeted practice.', strtolower($theme));
        }

        if ($missingConcepts !== []) {
            return sprintf('Review %s and practice explaining it in your own words.', $missingConcepts[0]);
        }

        if ($mergedFeedback !== null && $mergedFeedback !== '') {
            return 'Reread the question, then practice a clearer step-by-step explanation using key course terms.';
        }

        return null;
    }

    /**
     * @param  list<string>  $missingConcepts
     * @param  list<string>  $detectedConcepts
     * @return array{to_strengthen: list<array{theme: string, concepts: list<string>}>, demonstrated: list<array{theme: string, concepts: list<string>}>}
     */
    public static function aggregateLearningThemes(array $missingConcepts, array $detectedConcepts): array
    {
        return [
            'to_strengthen' => self::groupConceptsIntoThemes($missingConcepts),
            'demonstrated' => self::groupConceptsIntoThemes($detectedConcepts),
        ];
    }

    /**
     * @param  list<string>  $concepts
     * @return list<string>
     */
    public static function uniqueConcepts(array $concepts): array
    {
        $out = [];
        $seen = [];

        foreach ($concepts as $concept) {
            $label = trim((string) $concept);
            if ($label === '') {
                continue;
            }
            $key = mb_strtolower($label, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $label;
        }

        return $out;
    }

    private static function truncateText(string $text, int $max = 320): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $slice = mb_substr($text, 0, $max - 1);
        $lastSpace = mb_strrpos($slice, ' ');
        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.6)) {
            $slice = mb_substr($slice, 0, $lastSpace);
        }

        return rtrim($slice, '.,;:!').'…';
    }

    private static function textsSimilar(string $a, string $b): bool
    {
        $na = self::normalizeText($a);
        $nb = self::normalizeText($b);

        if ($na === '' || $nb === '') {
            return false;
        }

        if ($na === $nb) {
            return true;
        }

        if (str_contains($na, $nb) || str_contains($nb, $na)) {
            return true;
        }

        similar_text($na, $nb, $percent);
        if ($percent >= 72) {
            return true;
        }

        $wordsA = self::significantWords($na);
        $wordsB = self::significantWords($nb);

        if ($wordsA === [] || $wordsB === []) {
            return false;
        }

        $intersect = count(array_intersect($wordsA, $wordsB));
        $denominator = max(count($wordsA), count($wordsB));

        return $denominator > 0 && ($intersect / $denominator) >= 0.65;
    }

    private static function addsNewInfo(string $primary, string $secondary): bool
    {
        $primaryWords = self::significantWords(self::normalizeText($primary));
        $secondaryWords = self::significantWords(self::normalizeText($secondary));

        if ($secondaryWords === []) {
            return false;
        }

        $unique = array_diff($secondaryWords, $primaryWords);

        return count($unique) >= 3;
    }

    private static function pickPreferredText(string $a, string $b): string
    {
        $lenA = mb_strlen($a);
        $lenB = mb_strlen($b);

        $sweetA = $lenA >= 40 && $lenA <= 220;
        $sweetB = $lenB >= 40 && $lenB <= 220;

        if ($sweetA && ! $sweetB) {
            return $a;
        }

        if ($sweetB && ! $sweetA) {
            return $b;
        }

        if ($lenA >= 40 && $lenB >= 40) {
            if ($lenA !== $lenB) {
                return $lenA <= $lenB ? $a : $b;
            }

            return self::normalizeText($a) <= self::normalizeText($b) ? $a : $b;
        }

        if ($lenA !== $lenB) {
            return $lenA <= $lenB ? $a : $b;
        }

        return self::normalizeText($a) <= self::normalizeText($b) ? $a : $b;
    }

    private static function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        return preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
    }

    /**
     * @return list<string>
     */
    private static function significantWords(string $text): array
    {
        $stop = [
            'the', 'a', 'an', 'and', 'or', 'but', 'to', 'of', 'in', 'on', 'for', 'with', 'is', 'was', 'were',
            'be', 'been', 'being', 'that', 'this', 'it', 'as', 'at', 'by', 'from', 'student', 'answer', 'did',
            'not', 'any', 'no', 'provide', 'relevant', 'information', 'question',
        ];

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($words, fn (string $w) => mb_strlen($w) > 2 && ! in_array($w, $stop, true)));
    }

    /**
     * @param  list<string>  $keywords
     */
    private static function conceptMatchesTheme(string $concept, array $keywords): bool
    {
        $haystack = mb_strtolower($concept, 'UTF-8');

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
