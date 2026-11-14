<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\UserPracticeSession;
use App\Models\UserSeenQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentPracticeController extends Controller
{
    /**
     * Map a single question to the formatted client array using the persistent options map.
     */
    private function formatQuestionForClient($q, ?array $optionsMap = null): array
    {
        $result = [
            'id' => $q->id,
            'q_type' => $q->question_type,
            'question_text' => $q->question_text,
        ];

        if ($q->question_type === 'mcq') {
            if (!empty($optionsMap)) {
                $optionsByKey = $q->options->keyBy(fn ($o) => strtoupper(trim((string) $o->option_key)));
                $result['option_a'] = isset($optionsMap['A']) ? ($optionsByKey[strtoupper($optionsMap['A'])]->option_text ?? null) : null;
                $result['option_b'] = isset($optionsMap['B']) ? ($optionsByKey[strtoupper($optionsMap['B'])]->option_text ?? null) : null;
                $result['option_c'] = isset($optionsMap['C']) ? ($optionsByKey[strtoupper($optionsMap['C'])]->option_text ?? null) : null;
                $result['option_d'] = isset($optionsMap['D']) ? ($optionsByKey[strtoupper($optionsMap['D'])]->option_text ?? null) : null;
                $result['option_e'] = isset($optionsMap['E']) ? ($optionsByKey[strtoupper($optionsMap['E'])]->option_text ?? null) : null;
            } else {
                $sortedOptions = $q->options->sortBy(fn ($o) => $o->option_key)->values();
                $result['option_a'] = $sortedOptions[0]->option_text ?? null;
                $result['option_b'] = $sortedOptions[1]->option_text ?? null;
                $result['option_c'] = $sortedOptions[2]->option_text ?? null;
                $result['option_d'] = $sortedOptions[3]->option_text ?? null;
                $result['option_e'] = $sortedOptions[4]->option_text ?? null;
            }
        }

        return $result;
    }

    /**
     * Build and persist a deterministic shuffled options mapping per question for this practice session.
     */
    private function buildOptionsOrder($questions): array
    {
        $optionsOrder = [];
        $keys = ['A', 'B', 'C', 'D', 'E'];

        foreach ($questions as $q) {
            if ($q->question_type === 'mcq' && $q->options->isNotEmpty()) {
                $shuffled = $q->options->shuffle()->values();
                $map = [];
                foreach ($shuffled as $idx => $opt) {
                    if (isset($keys[$idx])) {
                        $map[$keys[$idx]] = strtoupper(trim((string) $opt->option_key));
                    }
                }
                $optionsOrder[$q->id] = $map;
            }
        }

        return $optionsOrder;
    }

    /**
     * Apply a fuzzy topic/course search by matching keywords.
     * Uses OR logic so a single typo doesn't kill the whole search.
     * Each keyword is matched as a substring (LIKE '%word%').
     */
    private function applyTopicSearch($query, string $topic, string $column = 'topic')
    {
        // Filter out short filler words so users can type naturally
        $stopWords = ['of', 'the', 'and', '&', 'in', 'a', 'an', 'to', 'for', 'on', 'at', 'by', 'with'];
        $words = array_values(array_filter(
            preg_split('/\s+/', trim($topic)),
            fn($w) => mb_strlen($w) > 1 && !in_array(mb_strtolower($w), $stopWords)
        ));

        if (empty($words)) {
            // Fallback: use the full input as-is
            $query->where($column, 'like', '%' . $topic . '%');
            return $query;
        }

        // Match ANY keyword (OR logic) - this is forgiving of typos
        // If the user types "Foundation liberal Afirican", it will still
        // match "Foundations of Liberal Studies & The African Identity"
        // because "Foundation" and "liberal" both hit.
        $query->where(function ($q) use ($words, $column) {
            foreach ($words as $word) {
                $q->orWhere($column, 'like', '%' . $word . '%');
            }
        });

        return $query;
    }

    /**
     * Scope question queries by matched topic or course IDs.
     */
    private function applyTopicOrCourseFilter($query, ?string $topic, array $courseIds = [])
    {
        if (empty($topic) && empty($courseIds)) {
            return $query;
        }

        return $query->where(function ($q) use ($topic, $courseIds) {
            $hasCondition = false;
            if (!empty($courseIds)) {
                $q->whereIn('question_bank.course_id', $courseIds);
                $hasCondition = true;
            }
            if (!empty($topic)) {
                if ($hasCondition) {
                    $q->orWhere('question_bank.topic', $topic)
                      ->orWhere('question_bank.topic', 'like', '%' . $topic . '%');
                } else {
                    $q->where(function ($sub) use ($topic) {
                        $sub->where('question_bank.topic', $topic)
                            ->orWhere('question_bank.topic', 'like', '%' . $topic . '%');
                    });
                }
            }
        });
    }

    public function generate(Request $request)
    {
        $request->validate([
            'count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'topic' => ['nullable', 'string', 'max:255'],
            'question_type' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $requestedCount = min(30, max(1, (int) $request->input('count', 20)));

        // Calculate available free questions and tokens
        $freeAvailable = max(0, (int) $user->free_questions_remaining);
        $tokensAvailable = max(0, (int) $user->tokens);
        $totalAvailable = $freeAvailable + $tokensAvailable;

        // If the user has exhausted both free questions and tokens, require top-up
        if ($totalAvailable <= 0) {
            $msg = "You've exhausted your free questions and tokens. Top up your tokens to keep unlocking new questions!";
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return back()->with('error', $msg);
        }

        // Allow users to finish all remaining tokens/free questions:
        // Automatically cap requestedCount to totalAvailable if requestedCount exceeds available balance
        if ($requestedCount > $totalAvailable) {
            $requestedCount = $totalAvailable;
        }

        // Resolve the user's input to the actual course name or topic stored in the DB.
        // This handles matching by Course Name/Code OR by Topic name (with fuzzy OR-matching).
        $resolvedTopic = null;
        $matchedCourseIds = [];
        if ($request->filled('topic')) {
            $userTopic = trim((string) $request->input('topic'));

            // 1. Check if userTopic matches a Course (name or code)
            $matchedCourses = Course::query()
                ->where('name', 'like', '%' . $userTopic . '%')
                ->orWhere('code', 'like', '%' . $userTopic . '%')
                ->get(['id', 'name', 'code']);

            if ($matchedCourses->isNotEmpty()) {
                $matchedCourseIds = $matchedCourses->pluck('id')->toArray();
                $resolvedTopic = $matchedCourses->first()->name;
            }

            // 2. Also check if userTopic matches a QuestionBank topic
            $checkQuery = QuestionBank::where('is_active', true);
            $this->applyTopicSearch($checkQuery, $userTopic, 'question_bank.topic');
            $dbTopic = $checkQuery->value('topic');
            if ($dbTopic) {
                $resolvedTopic = $dbTopic;
            } elseif (!$resolvedTopic) {
                $resolvedTopic = QuestionBank::where('is_active', true)
                    ->where('topic', 'like', '%' . $userTopic . '%')
                    ->value('topic');
            }

            // 3. Fallback: if no DB course or topic matched, retain user's input for dynamic AI generation
            if (!$resolvedTopic) {
                $resolvedTopic = $userTopic;
            }
        }

        $questionTypeParam = $request->input('question_type', 'all');
        $types = [];
        $typeGroups = [];

        switch ($questionTypeParam) {
            case 'mcqs':
                $types = ['mcq'];
                $typeGroups = [['mcq']];
                break;
            case 'theory':
                $types = ['theory', 'short_answer'];
                $typeGroups = [['theory', 'short_answer']];
                break;
            case 'fill_in':
                $types = ['fill_in'];
                $typeGroups = [['fill_in']];
                break;
            case 'mcqs_theory':
                $types = ['mcq', 'theory', 'short_answer'];
                $typeGroups = [['mcq'], ['theory', 'short_answer']];
                break;
            case 'mcqs_fill_in':
                $types = ['mcq', 'fill_in'];
                $typeGroups = [['mcq'], ['fill_in']];
                break;
            case 'theory_fill_in':
                $types = ['theory', 'short_answer', 'fill_in'];
                $typeGroups = [['theory', 'short_answer'], ['fill_in']];
                break;
            case 'all':
            default:
                $types = ['mcq', 'theory', 'short_answer', 'fill_in'];
                $typeGroups = [['mcq'], ['theory', 'short_answer'], ['fill_in']];
                break;
        }

        // ── Seen question IDs for this user (from the dedicated tracking table) ──
        $seenQuestionIds = UserSeenQuestion::where('user_id', $user->id)
            ->pluck('question_id')
            ->toArray();

        $collectedQuestions = collect();
        $groupCount = count($typeGroups);
        $targetPerGroup = (int) ceil($requestedCount / $groupCount);

        // ── Step 1: Collect questions per type group to ensure a balanced, authentic mix ──
        foreach ($typeGroups as $groupTypes) {
            $groupCollected = collect();
            $neededForGroup = $targetPerGroup;

            // 1a: Unseen questions on topic/course
            $unseenQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $groupTypes);
            if ($resolvedTopic || !empty($matchedCourseIds)) {
                $this->applyTopicOrCourseFilter($unseenQuery, $resolvedTopic, $matchedCourseIds);
            }
            if (!empty($seenQuestionIds)) {
                $unseenQuery->whereNotIn('id', $seenQuestionIds);
            }
            $unseen = $unseenQuery->inRandomOrder()->limit($neededForGroup)->get(['id']);
            $groupCollected = $groupCollected->concat($unseen);
            $neededForGroup -= $unseen->count();

            // 1b: Seen questions on topic/course
            if ($neededForGroup > 0 && !empty($seenQuestionIds)) {
                $seenQuery = QuestionBank::query()
                    ->where('is_active', true)
                    ->whereIn('question_type', $groupTypes)
                    ->whereIn('question_bank.id', $seenQuestionIds)
                    ->whereNotIn('question_bank.id', $groupCollected->pluck('id'))
                    ->join('user_seen_questions as usq', function ($join) use ($user) {
                        $join->on('question_bank.id', '=', 'usq.question_id')
                             ->where('usq.user_id', '=', $user->id);
                    })
                    ->orderBy('usq.seen_count', 'asc')
                    ->orderBy('usq.last_seen_at', 'asc');
                if ($resolvedTopic || !empty($matchedCourseIds)) {
                    $this->applyTopicOrCourseFilter($seenQuery, $resolvedTopic, $matchedCourseIds);
                }
                $seen = $seenQuery->limit($neededForGroup)->get(['question_bank.id as id']);
                $groupCollected = $groupCollected->concat($seen);
                $neededForGroup -= $seen->count();
            }

            // 1c: If on a specific course/topic and still needed, cycle from the SAME course/topic only!
            if ($neededForGroup > 0 && ($resolvedTopic || !empty($matchedCourseIds))) {
                $sameTopicCycle = QuestionBank::query()
                    ->where('is_active', true)
                    ->whereIn('question_type', $groupTypes);
                $this->applyTopicOrCourseFilter($sameTopicCycle, $resolvedTopic, $matchedCourseIds);
                $sameTopicCycle = $sameTopicCycle->inRandomOrder()->get(['id']);
                if ($sameTopicCycle->isNotEmpty()) {
                    while ($neededForGroup > 0) {
                        $take = min($neededForGroup, $sameTopicCycle->count());
                        $groupCollected = $groupCollected->concat($sameTopicCycle->take($take));
                        $neededForGroup -= $take;
                    }
                }
            } elseif ($neededForGroup > 0 && !$resolvedTopic && empty($matchedCourseIds)) {
                // If NO course was specified, expand across general topics
                $extraUnseenQuery = QuestionBank::query()
                    ->where('is_active', true)
                    ->whereIn('question_type', $groupTypes)
                    ->whereNotIn('id', array_merge($groupCollected->pluck('id')->toArray(), $seenQuestionIds));
                $extraUnseen = $extraUnseenQuery->inRandomOrder()->limit($neededForGroup)->get(['id']);
                $groupCollected = $groupCollected->concat($extraUnseen);
                $neededForGroup -= $extraUnseen->count();

                if ($neededForGroup > 0 && !empty($seenQuestionIds)) {
                    $extraSeenQuery = QuestionBank::query()
                        ->where('is_active', true)
                        ->whereIn('question_type', $groupTypes)
                        ->whereIn('question_bank.id', $seenQuestionIds)
                        ->whereNotIn('question_bank.id', $groupCollected->pluck('id'))
                        ->join('user_seen_questions as usq', function ($join) use ($user) {
                            $join->on('question_bank.id', '=', 'usq.question_id')
                                 ->where('usq.user_id', '=', $user->id);
                        })
                        ->orderBy('usq.seen_count', 'asc')
                        ->orderBy('usq.last_seen_at', 'asc');
                    $extraSeen = $extraSeenQuery->limit($neededForGroup)->get(['question_bank.id as id']);
                    $groupCollected = $groupCollected->concat($extraSeen);
                    $neededForGroup -= $extraSeen->count();
                }
            }

            $collectedQuestions = $collectedQuestions->concat($groupCollected);
        }

        $questions = $collectedQuestions->unique('id');

        // ── Step 2: Try dynamic AI generation for any shortfall ──
        $stillNeeded = $requestedCount - $questions->count();
        if ($stillNeeded > 0 && !empty($resolvedTopic)) {
            try {
                /** @var \App\Services\AiQuestionGenerationService $aiService */
                $aiService = app(\App\Services\AiQuestionGenerationService::class);
                $generated = $aiService->generateFromTopic(
                    $resolvedTopic,
                    $stillNeeded,
                    $types,
                    'medium',
                    $stillNeeded,
                    $user->level ?? '100'
                );

                if (!empty($generated) && is_array($generated)) {
                    foreach ($generated as $gq) {
                        $qText = trim((string) ($gq['question_text'] ?? ''));
                        if ($qText === '') continue;

                        $existing = QuestionBank::where('topic', $resolvedTopic)
                            ->where('question_text', $qText)
                            ->first();
                        if ($existing) continue;

                        $newQb = QuestionBank::create([
                            'course_id' => !empty($matchedCourseIds) ? $matchedCourseIds[0] : null,
                            'topic' => $resolvedTopic,
                            'question_text' => $qText,
                            'question_type' => $gq['question_type'] ?? 'mcq',
                            'difficulty' => $gq['difficulty'] ?? 'medium',
                            'correct_answer' => $gq['correct_answer'] ?? null,
                            'model_answer' => $gq['model_answer'] ?? $gq['correct_answer'] ?? null,
                            'marking_scheme' => $gq['marking_scheme'] ?? null,
                            'suggested_marks' => $gq['suggested_marks'] ?? 1,
                            'level' => $user->level ?? '100',
                            'is_active' => true,
                            'generated_by_ai' => true,
                            'created_by' => $user->id,
                            'lecturer_id' => $user->id,
                        ]);

                        if (($gq['question_type'] ?? 'mcq') === 'mcq' && !empty($gq['options'])) {
                            foreach ($gq['options'] as $opt) {
                                QuestionOption::create([
                                    'question_bank_id' => $newQb->id,
                                    'option_key' => strtoupper(substr((string) ($opt['key'] ?? 'A'), 0, 1)),
                                    'option_text' => $opt['text'] ?? '',
                                    'is_correct' => (bool) ($opt['is_correct'] ?? false),
                                ]);
                            }
                        }

                        $questions->push($newQb);
                        $stillNeeded--;
                        if ($stillNeeded <= 0) break;
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Dynamic AI question generation shortfall failed: ' . $e->getMessage());
            }
        }

        // ── Step 3: If shortfall remains, backfill from available database pool ──
        $stillNeeded = $requestedCount - $questions->count();
        if ($stillNeeded > 0) {
            $poolQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $types);
            if ($resolvedTopic || !empty($matchedCourseIds)) {
                $this->applyTopicOrCourseFilter($poolQuery, $resolvedTopic, $matchedCourseIds);
            }
            $pool = $poolQuery->whereNotIn('id', $questions->pluck('id')->filter())
                ->inRandomOrder()
                ->limit($stillNeeded)
                ->get(['id']);
            $questions = $questions->concat($pool);
            $stillNeeded -= $pool->count();
        }

        // ── Step 4: If still needed, cycle from available questions matching topic/course & type ──
        if ($stillNeeded > 0) {
            $cyclePoolQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $types);
            if ($resolvedTopic || !empty($matchedCourseIds)) {
                $this->applyTopicOrCourseFilter($cyclePoolQuery, $resolvedTopic, $matchedCourseIds);
            }
            $cyclePool = $cyclePoolQuery->inRandomOrder()->get(['id']);
            if ($cyclePool->isNotEmpty()) {
                while ($stillNeeded > 0) {
                    $take = min($stillNeeded, $cyclePool->count());
                    $questions = $questions->concat($cyclePool->take($take));
                    $stillNeeded -= $take;
                }
            }
        }

        $topic = $resolvedTopic ?? $request->input('topic', 'General Knowledge');
        $questions = $questions->shuffle()->take($requestedCount);
        $actualCount = $questions->count();

        if ($actualCount < 1) {
            $msg = $resolvedTopic
                ? "No questions found for the course \"{$resolvedTopic}\". Please try another question type or course."
                : "There are no questions available matching your criteria. Please try another question type or course.";
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 404);
            }
            return back()->with('error', $msg);
        }

        // Fetch full question records with options
        $fetchedDb = QuestionBank::whereIn('id', $questions->pluck('id')->filter())->with('options')->get()->keyBy('id');
        $fetchedQuestionsAll = collect();
        foreach ($questions as $qObj) {
            $qid = is_object($qObj) ? $qObj->id : $qObj;
            if (isset($fetchedDb[$qid])) {
                $fetchedQuestionsAll->push($fetchedDb[$qid]);
            }
        }
        $actualCount = $fetchedQuestionsAll->count();

        // Calculate exact cost dynamically based on actual returned questions:
        // Free questions are consumed first, then tokens (1 token per question)
        $freeQuestionsUsed = min($actualCount, $freeAvailable);
        $paidQuestionsCount = max(0, $actualCount - $freeQuestionsUsed);

        $session = null;
        $formattedQuestions = null;

        try {
            DB::transaction(function () use (
                $user,
                $freeQuestionsUsed,
                $paidQuestionsCount,
                $topic,
                $fetchedQuestionsAll,
                &$session,
                &$formattedQuestions
            ) {
                // Deduct free questions first, then tokens (ensuring values never drop below 0)
                $user->free_questions_remaining = max(0, (int) $user->free_questions_remaining - $freeQuestionsUsed);
                $user->tokens = max(0, (int) $user->tokens - $paidQuestionsCount);
                $user->save();

                $optionsOrder = $this->buildOptionsOrder($fetchedQuestionsAll);

                $sessionData = [
                    'user_id' => $user->id,
                    'topic' => $topic,
                    'questions' => $fetchedQuestionsAll->pluck('id')->toArray(),
                ];

                // Safely handle options_order if column does not exist on database yet
                if (\Illuminate\Support\Facades\Schema::hasColumn('user_practice_sessions', 'options_order')) {
                    $sessionData['options_order'] = $optionsOrder;
                }

                $session = UserPracticeSession::create($sessionData);

                $formattedQuestions = $fetchedQuestionsAll->map(function ($q) use ($optionsOrder) {
                    return $this->formatQuestionForClient($q, $optionsOrder[$q->id] ?? null);
                })->values();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Practice generation transaction failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'topic' => $topic,
                'exception' => $e,
            ]);

            $errMsg = 'Failed to generate practice session. Your tokens were not deducted. Please try again.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $errMsg], 500);
            }
            return back()->with('error', $errMsg);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'practiceSession' => $session,
                'questions' => $formattedQuestions,
            ]);
        }

        return redirect()->route('student.practice.take', $session->id);
    }

    public function take(UserPracticeSession $session)
    {
        // Ensure user owns this session and hasn't completed it yet
        if ($session->user_id !== auth()->id() || $session->completed_at !== null) {
            abort(403);
        }

        $optionsOrder = $session->options_order ?? [];

        $questions = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
            ->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
            ->map(function ($q) use ($optionsOrder) {
                return $this->formatQuestionForClient($q, $optionsOrder[$q->id] ?? null);
            });

        return Inertia::render('Student/Practice/Take', [
            'practiceSession' => $session,
            'questions' => $questions,
        ]);
    }

    /**
     * Retrieve the best reference / model answer for a question.
     */
    private function getQuestionReferenceAnswer(QuestionBank $q): string
    {
        $correct = trim((string) ($q->correct_answer ?? ''));
        if ($correct !== '') {
            return $correct;
        }

        $model = trim((string) ($q->model_answer ?? ''));
        if ($model !== '') {
            return $model;
        }

        return trim((string) ($q->marking_scheme ?? ''));
    }

    /**
     * Fair local evaluation of theory / short answer responses when AI is offline.
     */
    private function evaluateTheoryLocally(string $userAnswer, string $referenceAnswer): array
    {
        $userClean = trim($userAnswer);
        $refClean = trim($referenceAnswer);

        if ($userClean === '') {
            return [
                'is_correct' => false,
                'marks' => 0.0,
                'feedback' => 'No answer was provided. Review the model answer above.',
            ];
        }

        if ($refClean === '') {
            return [
                'is_correct' => true,
                'marks' => 1.0,
                'feedback' => 'Your answer has been submitted and recorded.',
            ];
        }

        $userLower = mb_strtolower($userClean);
        $refLower = mb_strtolower($refClean);

        // 1. Direct or substring match
        if ($userLower === $refLower || str_contains($refLower, $userLower)) {
            return [
                'is_correct' => true,
                'marks' => 1.0,
                'feedback' => 'Excellent! Your answer directly matches the model answer.',
            ];
        }

        if (str_contains($userLower, $refLower)) {
            return [
                'is_correct' => true,
                'marks' => 1.0,
                'feedback' => 'Excellent! Your comprehensive answer covers the model answer.',
            ];
        }

        // 2. Keyword tokenization & matching (filter out common stopwords)
        $stopWords = [
            'a','an','the','and','or','but','if','because','as','what','which','this','that','these','those',
            'then','so','than','such','both','through','about','for','is','are','was','were','be','been','being',
            'have','has','had','do','does','did','to','from','in','out','on','off','over','under','again',
            'further','once','here','there','when','where','why','how','all','any','both','each','few','more',
            'most','other','some','such','no','nor','not','only','own','same','so','than','too','very','s','t',
            'can','will','just','don','should','now','it','its','by','with','at','of'
        ];
        $stopWordsFlip = array_flip($stopWords);

        $extractWords = function (string $text) use ($stopWordsFlip): array {
            $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($text));
            $words = preg_split('/\s+/u', (string) $cleaned, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $filtered = [];
            foreach ($words as $w) {
                if (mb_strlen($w) > 1 && !isset($stopWordsFlip[$w])) {
                    $filtered[$w] = true;
                }
            }
            return array_keys($filtered);
        };

        $userWords = $extractWords($userClean);
        $refWords = $extractWords($refClean);

        if (empty($refWords)) {
            return [
                'is_correct' => true,
                'marks' => 1.0,
                'feedback' => 'Your answer has been recorded. Review the model answer above.',
            ];
        }

        $matchedWords = array_intersect($userWords, $refWords);
        $matchCount = count($matchedWords);
        $userWordCount = count($userWords);
        $refWordCount = count($refWords);

        $userCoverage = $userWordCount > 0 ? ($matchCount / $userWordCount) : 0;
        $refCoverage = $refWordCount > 0 ? ($matchCount / $refWordCount) : 0;

        if ($matchCount >= 3 || $refCoverage >= 0.5 || ($matchCount >= 2 && $userCoverage >= 0.6)) {
            $marks = ($refCoverage >= 0.7 || $matchCount >= 5) ? 1.0 : 0.8;
            return [
                'is_correct' => true,
                'marks' => $marks,
                'feedback' => 'Great job! Your answer covers key concepts from the model answer.',
            ];
        }

        if ($matchCount >= 1 || $userCoverage >= 0.4 || mb_strlen($userClean) >= 20) {
            $matchedSample = !empty($matchedWords) ? ' (' . implode(', ', array_slice($matchedWords, 0, 3)) . ')' : '';
            return [
                'is_correct' => false,
                'marks' => 0.5,
                'feedback' => 'Good attempt! You identified relevant concepts' . $matchedSample . '. Compare with the complete model answer to see additional key points.',
            ];
        }

        return [
            'is_correct' => false,
            'marks' => 0.0,
            'feedback' => 'Your answer does not sufficiently address the expected points. Study the model answer above.',
        ];
    }

    private function gradeNormally($questions, $responses, array $optionsOrder = [])
    {
        $score = 0.0;
        $total = count($questions);
        $formattedResponses = [];

        foreach ($questions as $q) {
            $userAnswerRaw = $responses[$q->id] ?? null;
            $optionsMap = $optionsOrder[$q->id] ?? null;
            $referenceAnswer = $this->getQuestionReferenceAnswer($q);
            $userAnswerTrimmed = trim((string) ($userAnswerRaw ?? ''));

            $originalAnswerKey = $userAnswerRaw;
            if ($q->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
            }

            if ($q->question_type === 'mcq') {
                $correctOriginalKey = strtoupper(trim((string) ($q->correct_answer ?? '')));
                $isCorrect = ($userAnswerTrimmed !== '') && (strtoupper(trim((string) $originalAnswerKey)) === $correctOriginalKey);
                $marks = $isCorrect ? 1.0 : 0.0;
                $score += $marks;

                $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginalKey, $optionsMap, true))) ? $k : $q->correct_answer;
                $userAnswerText = $userAnswerRaw;
                $correctAnswerText = $q->correct_answer;

                $userOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                if ($userOpt) {
                    $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                }
                $correctOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginalKey);
                if ($correctOpt) {
                    $correctAnswerText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                } else {
                    $correctAnswerText = $correctDisplayed;
                }

                $aiFeedback = $isCorrect
                    ? "Correct! Option {$correctDisplayed} is the right answer."
                    : ($userAnswerTrimmed !== '' ? "Incorrect. The correct option is Option {$correctDisplayed}." : "No answer provided. The correct option is Option {$correctDisplayed}.");
            } elseif ($q->question_type === 'fill_in') {
                $correctDisplayed = $referenceAnswer;
                $correctAnswerText = $referenceAnswer;
                $userAnswerText = $userAnswerRaw;

                $normUser = strtolower(trim($userAnswerTrimmed));
                $normRef = strtolower(trim($referenceAnswer));
                $isExact = ($normUser !== '' && $normRef !== '' && ($normUser === $normRef || str_contains($normRef, $normUser)));

                $isCorrect = $isExact;
                $marks = $isCorrect ? 1.0 : 0.0;
                $score += $marks;

                $aiFeedback = $isCorrect
                    ? "Correct! Your answer matches the expected response."
                    : ($userAnswerTrimmed !== '' ? "Incorrect. Review the expected answer: {$referenceAnswer}" : "No answer provided. Expected: {$referenceAnswer}");
            } else {
                // Theory / short_answer
                $correctDisplayed = $referenceAnswer;
                $correctAnswerText = $referenceAnswer;
                $userAnswerText = $userAnswerRaw;

                $eval = $this->evaluateTheoryLocally($userAnswerTrimmed, $referenceAnswer);
                $isCorrect = $eval['is_correct'];
                $marks = $eval['marks'];
                $score += $marks;
                $aiFeedback = $eval['feedback'];
            }

            $formattedResponses[$q->id] = [
                'user_answer' => $userAnswerRaw,
                'correct_answer' => $correctDisplayed,
                'user_answer_text' => $userAnswerText,
                'correct_answer_text' => $correctAnswerText,
                'is_correct' => $isCorrect,
                'marks' => $marks,
                'ai_feedback' => $aiFeedback,
            ];
        }

        return [
            'score' => $total > 0 ? round(($score / $total) * 100) : 0,
            'responses' => $formattedResponses
        ];
    }

    public function submit(Request $request, UserPracticeSession $session)
    {
        if ($session->user_id !== $request->user()->id || $session->completed_at !== null) {
            abort(403);
        }

        $validated = $request->validate([
            'responses' => 'array',
        ]);

        $responses = $validated['responses'] ?? [];
        $questions = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
            ->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values();

        $graded = $this->gradeWithAiSynchronously($questions, $responses, $session->options_order ?? []);

        $session->update([
            'responses'    => $graded['responses'],
            'score'        => $graded['score'],
            'completed_at' => now(),
        ]);

        // ── Record completed questions in the seen-question tracker ──
        $now = now();
        $upsertRows = array_map(fn ($qId) => [
            'user_id'      => $session->user_id,
            'question_id'  => $qId,
            'seen_count'   => 1,
            'last_seen_at' => $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ], $session->questions ?? []);

        if (!empty($upsertRows)) {
            DB::table('user_seen_questions')->upsert(
                $upsertRows,
                ['user_id', 'question_id'],
                ['seen_count' => DB::raw('seen_count + 1'),
                 'last_seen_at' => $now,
                 'updated_at'   => $now]
            );
        }

        if ($request->wantsJson()) {
            $optionsOrder = $session->options_order ?? [];
            $questionsForResults = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
                ->sortBy(function ($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
                ->map(function ($q) use ($optionsOrder) {
                    $referenceAnswer = $this->getQuestionReferenceAnswer($q);
                    $optionsMap = $optionsOrder[$q->id] ?? null;
                    if ($q->question_type === 'mcq' && $optionsMap) {
                        $correctOriginal = strtoupper(trim((string) $q->correct_answer));
                        $q->correct_option = array_search($correctOriginal, $optionsMap, true) ?: $q->correct_answer;
                    } else {
                        $q->correct_option = $referenceAnswer;
                    }
                    $q->model_answer = $q->model_answer ?: $referenceAnswer;
                    return $q;
                });
            return response()->json([
                'practiceSession' => $session,
                'questions'       => $questionsForResults,
                'message'         => 'Practice submitted successfully! Here is your Assessiva feedback.',
            ]);
        }

        return redirect()->route('student.practice.result', $session->id)->with('success', 'Practice submitted successfully! Here is your Assessiva feedback.');
    }

    private function gradeWithAiSynchronously($questions, $responses, array $optionsOrder = [])
    {
        $openai = config('marking.openai');
        if (empty($openai['key'])) {
            return $this->gradeNormally($questions, $responses, $optionsOrder);
        }

        $promptData = [];
        $indexedQuestions = [];
        foreach ($questions as $q) {
            $userAnswerRaw = $responses[$q->id] ?? null;
            $optionsMap = $optionsOrder[$q->id] ?? null;

            $originalAnswerKey = $userAnswerRaw;
            if ($q->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
            }

            $referenceAnswer = $this->getQuestionReferenceAnswer($q);
            $userAnswerText = $userAnswerRaw;
            $correctText = $referenceAnswer;
            
            if ($q->question_type === 'mcq') {
                $correctOriginalKey = strtoupper(trim((string) ($q->correct_answer ?? '')));
                $userOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                if ($userOpt) {
                    $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                }
                $correctOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginalKey);
                $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginalKey, $optionsMap, true))) ? $k : $q->correct_answer;
                if ($correctOpt) {
                    $correctText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                } else {
                    $correctText = $correctDisplayed;
                }
            }

            $promptData[] = [
                'id' => $q->id,
                'type' => $q->question_type,
                'question' => strip_tags($q->question_text),
                'correct_answer' => strip_tags($correctText),
                'student_answer' => strip_tags($userAnswerText ?: 'No answer provided.'),
                'model_answer' => strip_tags($q->model_answer ?? $referenceAnswer),
                'marking_scheme' => strip_tags($q->marking_scheme ?? '')
            ];
            $indexedQuestions[$q->id] = $q;
        }

        $prompt = "You are an AI tutor marking a practice quiz. Evaluate the following questions.\n";
        $prompt .= "For MCQs: The student answer is given. Explain why the correct option is right, and why the student's chosen option is wrong (if they got it wrong). If they got it right, praise them and explain why it's right.\n";
        $prompt .= "For Theory/Fill-in: Be extremely forgiving. If the student has the general idea or demonstrates partial understanding, DO NOT mark them entirely wrong. Award partial credit (e.g. set 'marks' to a decimal like 0.5 or 0.8) instead of 0, and provide constructive feedback on what they missed.\n";
        $prompt .= "CRITICAL: Explain the answer very well for easy understanding, but keep your feedback concise. Your feedback MUST NOT exceed 4 sentences in length.\n";
        $prompt .= "Return ONLY a JSON array, exactly in the order provided, matching this structure:\n";
        $prompt .= "[\n  {\n    \"id\": 123,\n    \"is_correct\": true,\n    \"marks\": 0.5,\n    \"feedback\": \"Your explanation...\"\n  }\n]\n\n";
        $prompt .= "Here is the data:\n" . json_encode($promptData);

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($openai['key'])
                ->timeout(30)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an AI grader. Output strictly valid JSON without markdown formatting.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.2
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (!$content) {
                    return $this->gradeNormally($questions, $responses, $optionsOrder);
                }
                $jsonStr = trim(preg_replace('/^```json|```$/i', '', trim($content)));
                $results = json_decode($jsonStr, true);
                
                if (is_array($results)) {
                    $formattedResponses = [];
                    $correctCount = 0;
                    $total = count($questions);
                    
                    foreach ($results as $res) {
                        if (!isset($res['id']) || !isset($indexedQuestions[$res['id']])) {
                            continue;
                        }
                        $qId = $res['id'];
                        $isCorrect = (bool) ($res['is_correct'] ?? false);
                        $marks = isset($res['marks']) && is_numeric($res['marks']) ? (float) $res['marks'] : ($isCorrect ? 1.0 : 0.0);
                        if ($isCorrect && $marks <= 0) {
                            $marks = 1.0;
                        }
                        $correctCount += $marks;

                        $qItem = $indexedQuestions[$qId];
                        $optionsMap = $optionsOrder[$qId] ?? null;
                        $referenceAnswer = $this->getQuestionReferenceAnswer($qItem);

                        $userAnswerRaw = $responses[$qId] ?? null;
                        $originalAnswerKey = $userAnswerRaw;
                        if ($qItem->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                            $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                            $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
                        }

                        $userAnswerText = $userAnswerRaw;
                        if ($qItem->question_type === 'mcq') {
                            $correctOriginal = strtoupper(trim((string) ($qItem->correct_answer ?? '')));
                            $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginal, $optionsMap, true))) ? $k : $qItem->correct_answer;
                            $userOpt = $qItem->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                            if ($userOpt) {
                                $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                            }
                            $correctOpt = $qItem->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginal);
                            if ($correctOpt) {
                                $correctAnswerText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                            } else {
                                $correctAnswerText = $correctDisplayed;
                            }
                        } else {
                            $correctDisplayed = $referenceAnswer;
                            $correctAnswerText = $referenceAnswer;
                        }

                        $formattedResponses[$qId] = [
                            'user_answer' => $userAnswerRaw,
                            'correct_answer' => $correctDisplayed,
                            'user_answer_text' => $userAnswerText,
                            'correct_answer_text' => $correctAnswerText,
                            'is_correct' => $isCorrect,
                            'marks' => $marks,
                            'ai_feedback' => $res['feedback'] ?? ''
                        ];
                    }
                    
                    // Fill any missing
                    foreach ($questions as $q) {
                        if (!isset($formattedResponses[$q->id])) {
                            $optionsMap = $optionsOrder[$q->id] ?? null;
                            $referenceAnswer = $this->getQuestionReferenceAnswer($q);

                            $userAnswerRaw = $responses[$q->id] ?? null;
                            $originalAnswerKey = $userAnswerRaw;
                            if ($q->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                                $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                                $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
                            }

                            $userAnswerText = $userAnswerRaw;
                            if ($q->question_type === 'mcq') {
                                $correctOriginal = strtoupper(trim((string) ($q->correct_answer ?? '')));
                                $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginal, $optionsMap, true))) ? $k : $q->correct_answer;
                                $userOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                                if ($userOpt) {
                                    $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                                }
                                $correctOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginal);
                                if ($correctOpt) {
                                    $correctAnswerText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                                } else {
                                    $correctAnswerText = $correctDisplayed;
                                }
                            } else {
                                $correctDisplayed = $referenceAnswer;
                                $correctAnswerText = $referenceAnswer;
                            }

                            $formattedResponses[$q->id] = [
                                'user_answer' => $userAnswerRaw,
                                'correct_answer' => $correctDisplayed,
                                'user_answer_text' => $userAnswerText,
                                'correct_answer_text' => $correctAnswerText,
                                'is_correct' => false,
                                'marks' => 0,
                                'ai_feedback' => ''
                            ];
                        }
                    }

                    return [
                        'score' => $total > 0 ? round(($correctCount / $total) * 100) : 0,
                        'responses' => $formattedResponses
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Bulk grading failed: " . $e->getMessage());
        }

        return $this->gradeNormally($questions, $responses, $optionsOrder);
    }

    public function result(UserPracticeSession $session)
    {
        if ($session->user_id !== auth()->id() || $session->completed_at === null) {
            abort(403);
        }

        $optionsOrder = $session->options_order ?? [];

        $questions = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
            ->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
            ->map(function($q) use ($optionsOrder) {
                $referenceAnswer = $this->getQuestionReferenceAnswer($q);
                $optionsMap = $optionsOrder[$q->id] ?? null;
                if ($q->question_type === 'mcq' && $optionsMap) {
                    $correctOriginal = strtoupper(trim((string) $q->correct_answer));
                    $correctDisplayed = array_search($correctOriginal, $optionsMap, true) ?: $q->correct_answer;
                    $q->correct_option = $correctDisplayed;

                    $optionsByKey = $q->options->keyBy(fn ($o) => strtoupper(trim((string) $o->option_key)));
                    $q->option_a = isset($optionsMap['A']) ? ($optionsByKey[strtoupper($optionsMap['A'])]->option_text ?? null) : null;
                    $q->option_b = isset($optionsMap['B']) ? ($optionsByKey[strtoupper($optionsMap['B'])]->option_text ?? null) : null;
                    $q->option_c = isset($optionsMap['C']) ? ($optionsByKey[strtoupper($optionsMap['C'])]->option_text ?? null) : null;
                    $q->option_d = isset($optionsMap['D']) ? ($optionsByKey[strtoupper($optionsMap['D'])]->option_text ?? null) : null;
                    $q->option_e = isset($optionsMap['E']) ? ($optionsByKey[strtoupper($optionsMap['E'])]->option_text ?? null) : null;
                } else {
                    $q->correct_option = $referenceAnswer;
                }
                $q->model_answer = $q->model_answer ?: $referenceAnswer;
                return $q;
            });

        return Inertia::render('Student/Practice/Result', [
            'practiceSession' => $session,
            'questions' => $questions,
        ]);
    }
}
