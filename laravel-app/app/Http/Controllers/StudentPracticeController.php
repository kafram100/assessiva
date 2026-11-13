<?php

namespace App\Http\Controllers;

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

        // Resolve the user's input to the actual course name stored in the DB.
        // This handles typos, partial names, etc. by using fuzzy OR-matching.
        // If the topic is not yet in the DB, we retain the user's input for dynamic AI generation.
        $resolvedTopic = null;
        if ($request->filled('topic')) {
            $userTopic = trim((string) $request->input('topic'));
            $checkQuery = QuestionBank::where('is_active', true);
            $this->applyTopicSearch($checkQuery, $userTopic);
            $resolvedTopic = $checkQuery->value('topic'); // get actual DB topic name

            if (!$resolvedTopic) {
                // Secondary check: partial LIKE search
                $resolvedTopic = QuestionBank::where('is_active', true)
                    ->where('topic', 'like', '%' . $userTopic . '%')
                    ->value('topic');
            }

            if (!$resolvedTopic) {
                // Topic is new: accept user's topic for dynamic AI generation and retrieval
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

            // 1a: Unseen questions on topic (if topic provided)
            $unseenQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $groupTypes);
            if ($resolvedTopic) {
                $unseenQuery->where('topic', $resolvedTopic);
            }
            if (!empty($seenQuestionIds)) {
                $unseenQuery->whereNotIn('id', $seenQuestionIds);
            }
            $unseen = $unseenQuery->inRandomOrder()->limit($neededForGroup)->get(['id']);
            $groupCollected = $groupCollected->concat($unseen);
            $neededForGroup -= $unseen->count();

            // 1b: Seen questions on topic
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
                if ($resolvedTopic) {
                    $seenQuery->where('question_bank.topic', $resolvedTopic);
                }
                $seen = $seenQuery->limit($neededForGroup)->get(['question_bank.id as id']);
                $groupCollected = $groupCollected->concat($seen);
                $neededForGroup -= $seen->count();
            }

            // 1c: If on a specific course and still needed, cycle from the SAME course only!
            if ($neededForGroup > 0 && $resolvedTopic) {
                $sameTopicCycle = QuestionBank::query()
                    ->where('is_active', true)
                    ->where('topic', $resolvedTopic)
                    ->whereIn('question_type', $groupTypes)
                    ->inRandomOrder()
                    ->get(['id']);
                if ($sameTopicCycle->isNotEmpty()) {
                    while ($neededForGroup > 0) {
                        $take = min($neededForGroup, $sameTopicCycle->count());
                        $groupCollected = $groupCollected->concat($sameTopicCycle->take($take));
                        $neededForGroup -= $take;
                    }
                }
            } elseif ($neededForGroup > 0 && !$resolvedTopic) {
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
            if ($resolvedTopic) {
                $poolQuery->where('topic', $resolvedTopic);
            }
            $pool = $poolQuery->whereNotIn('id', $questions->pluck('id')->filter())
                ->inRandomOrder()
                ->limit($stillNeeded)
                ->get(['id']);
            $questions = $questions->concat($pool);
            $stillNeeded -= $pool->count();
        }

        // ── Step 4: If still needed, cycle from available questions matching topic & type ──
        if ($stillNeeded > 0) {
            $cyclePoolQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $types);
            if ($resolvedTopic) {
                $cyclePoolQuery->where('topic', $resolvedTopic);
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

        // Deduct free questions first, then tokens (ensuring values never drop below 0)
        $user->free_questions_remaining = max(0, (int) $user->free_questions_remaining - $freeQuestionsUsed);
        $user->tokens = max(0, (int) $user->tokens - $paidQuestionsCount);
        $user->save();

        $optionsOrder = $this->buildOptionsOrder($fetchedQuestionsAll);

        $session = UserPracticeSession::create([
            'user_id' => $user->id,
            'topic' => $topic,
            'questions' => $fetchedQuestionsAll->pluck('id')->toArray(),
            'options_order' => $optionsOrder,
        ]);

        if ($request->wantsJson()) {
            $formattedQuestions = $fetchedQuestionsAll->map(function ($q) use ($optionsOrder) {
                return $this->formatQuestionForClient($q, $optionsOrder[$q->id] ?? null);
            })->values();

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

    private function gradeNormally($questions, $responses, array $optionsOrder = [])
    {
        $score = 0;
        $total = count($questions);
        $formattedResponses = [];

        foreach ($questions as $q) {
            $userAnswerRaw = $responses[$q->id] ?? null;
            $optionsMap = $optionsOrder[$q->id] ?? null;

            $originalAnswerKey = $userAnswerRaw;
            if ($q->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
            }

            $correctOriginalKey = strtoupper(trim((string) ($q->correct_answer ?? '')));
            $isCorrect = false;
            if ($userAnswerRaw !== null && $userAnswerRaw !== '') {
                if ($q->question_type === 'mcq') {
                    $isCorrect = (strtoupper(trim((string) $originalAnswerKey)) === $correctOriginalKey);
                } else {
                    $isCorrect = (strtolower(trim((string) $userAnswerRaw)) === strtolower($correctOriginalKey));
                }
            }

            if ($isCorrect) {
                $score++;
            }

            $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginalKey, $optionsMap, true))) ? $k : $q->correct_answer;

            $userAnswerText = $userAnswerRaw;
            $correctAnswerText = $q->correct_answer;
            if ($q->question_type === 'mcq') {
                $userOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                if ($userOpt) {
                    $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                }
                $correctOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginalKey);
                if ($correctOpt) {
                    $correctAnswerText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                }
            }

            $formattedResponses[$q->id] = [
                'user_answer' => $userAnswerRaw,
                'correct_answer' => $correctDisplayed,
                'user_answer_text' => $userAnswerText,
                'correct_answer_text' => $correctAnswerText,
                'is_correct' => $isCorrect,
                'marks' => $isCorrect ? 1 : 0,
                'ai_feedback' => ''
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
                    $optionsMap = $optionsOrder[$q->id] ?? null;
                    if ($optionsMap) {
                        $correctOriginal = strtoupper(trim((string) $q->correct_answer));
                        $q->correct_option = array_search($correctOriginal, $optionsMap, true) ?: $q->correct_answer;
                    } else {
                        $q->correct_option = $q->correct_answer;
                    }
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

            $correctOriginalKey = strtoupper(trim((string) ($q->correct_answer ?? '')));
            $userAnswerText = $userAnswerRaw;
            $correctText = $q->correct_answer;
            
            if ($q->question_type === 'mcq') {
                $userOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                if ($userOpt) {
                    $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                }
                $correctOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginalKey);
                $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginalKey, $optionsMap, true))) ? $k : $q->correct_answer;
                if ($correctOpt) {
                    $correctText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                }
            }

            $promptData[] = [
                'id' => $q->id,
                'type' => $q->question_type,
                'question' => strip_tags($q->question_text),
                'correct_answer' => strip_tags($correctText),
                'student_answer' => strip_tags($userAnswerText ?: 'No answer provided.'),
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
                        $correctOriginal = strtoupper(trim((string) ($qItem->correct_answer ?? '')));
                        $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginal, $optionsMap, true))) ? $k : $qItem->correct_answer;
                        
                        $userAnswerRaw = $responses[$qId] ?? null;
                        $originalAnswerKey = $userAnswerRaw;
                        if ($qItem->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                            $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                            $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
                        }

                        $userAnswerText = $userAnswerRaw;
                        $correctAnswerText = $qItem->correct_answer;
                        if ($qItem->question_type === 'mcq') {
                            $userOpt = $qItem->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                            if ($userOpt) {
                                $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                            }
                            $correctOpt = $qItem->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginal);
                            if ($correctOpt) {
                                $correctAnswerText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                            }
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
                            $correctOriginal = strtoupper(trim((string) ($q->correct_answer ?? '')));
                            $correctDisplayed = ($optionsMap && ($k = array_search($correctOriginal, $optionsMap, true))) ? $k : $q->correct_answer;

                            $userAnswerRaw = $responses[$q->id] ?? null;
                            $originalAnswerKey = $userAnswerRaw;
                            if ($q->question_type === 'mcq' && $userAnswerRaw && $optionsMap) {
                                $submittedLetter = strtoupper(trim((string) $userAnswerRaw));
                                $originalAnswerKey = $optionsMap[$submittedLetter] ?? $submittedLetter;
                            }

                            $userAnswerText = $userAnswerRaw;
                            $correctAnswerText = $q->correct_answer;
                            if ($q->question_type === 'mcq') {
                                $userOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === strtoupper(trim((string) $originalAnswerKey)));
                                if ($userOpt) {
                                    $userAnswerText = "Option {$userAnswerRaw}: {$userOpt->option_text}";
                                }
                                $correctOpt = $q->options->firstWhere(fn ($o) => strtoupper(trim((string) $o->option_key)) === $correctOriginal);
                                if ($correctOpt) {
                                    $correctAnswerText = "Option {$correctDisplayed}: {$correctOpt->option_text}";
                                }
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
                $optionsMap = $optionsOrder[$q->id] ?? null;
                if ($optionsMap) {
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
                    $q->correct_option = $q->correct_answer;
                }
                return $q;
            });

        return Inertia::render('Student/Practice/Result', [
            'practiceSession' => $session,
            'questions' => $questions,
        ]);
    }
}
