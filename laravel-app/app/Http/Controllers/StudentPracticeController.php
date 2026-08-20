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
        $user = $request->user();

        $requestedCount = min(30, max(1, (int) $request->input('count', 20)));

        // Calculate worst-case token cost before generating
        $freeAvailable = max(0, $user->free_questions_remaining);
        $paidQuestionsCount = max(0, $requestedCount - $freeAvailable);
        $maxTokenCost = $paidQuestionsCount * 1.5;

        if ($paidQuestionsCount > 0 && $user->tokens < $maxTokenCost) {
            if ($user->free_questions_remaining == 0) {
                if ($user->tokens == 0) {
                    $msg = "You've exhausted your free questions and tokens. Top up your tokens to keep unlocking new questions!";
                } else {
                    $msg = "Insufficient tokens for this request. You need up to {$maxTokenCost} tokens, but you only have {$user->tokens}. Please top up your tokens or request fewer questions.";
                }
            } else {
                $msg = "You only have {$user->free_questions_remaining} free questions left and not enough tokens to cover the rest. Please top up your tokens or request fewer questions.";
            }

            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return back()->with('error', $msg);
        }

        // Resolve the user's input to the actual course name stored in the DB.
        // This handles typos, partial names, etc. by using fuzzy OR-matching
        // to find the best-matching course, then using its exact name everywhere.
        $resolvedTopic = null;
        if ($request->filled('topic')) {
            $checkQuery = QuestionBank::where('is_active', true);
            $this->applyTopicSearch($checkQuery, $request->topic);
            $resolvedTopic = $checkQuery->value('topic'); // get the actual DB topic name

            if (!$resolvedTopic) {
                $msg = 'This course is not available in our question bank. Please try a different course.';
                if ($request->wantsJson()) {
                    return response()->json(['error' => $msg], 400);
                }
                return back()->with('error', $msg);
            }
        }

        $questionTypeParam = $request->input('question_type', 'all');
        $types = [];
        switch ($questionTypeParam) {
            case 'mcqs':
                $types = ['mcq'];
                break;
            case 'theory':
                $types = ['theory'];
                break;
            case 'fill_in':
                $types = ['fill_in'];
                break;
            case 'mcqs_theory':
                $types = ['mcq', 'theory'];
                break;
            case 'mcqs_fill_in':
                $types = ['mcq', 'fill_in'];
                break;
            case 'theory_fill_in':
                $types = ['theory', 'fill_in'];
                break;
            case 'all':
            default:
                $types = ['mcq', 'theory', 'fill_in', 'short_answer'];
                break;
        }

        // ── Seen question IDs for this user (from the dedicated tracking table) ──
        // Only questions the user has actually *completed* are in this table.
        // Abandoned sessions do NOT appear here.
        $seenQuestionIds = UserSeenQuestion::where('user_id', $user->id)
            ->pluck('question_id')
            ->toArray();

        // ── Step 1: Unseen questions of the selected type (highest priority) ──
        $unseenQuery = QuestionBank::query()
            ->where('is_active', true)
            ->whereIn('question_type', $types);
        if ($resolvedTopic) {
            $unseenQuery->where('topic', $resolvedTopic);
        }
        if (!empty($seenQuestionIds)) {
            $unseenQuery->whereNotIn('id', $seenQuestionIds);
        }
        $unseenQuestions = $unseenQuery->inRandomOrder()->limit($requestedCount)->get(['id']);

        // ── Step 2: Backfill with least-recently-seen questions (round-robin, same type) ──
        // Order: fewest times seen → least recently seen. Never random repeat.
        $collectedIds = $unseenQuestions->pluck('id')->toArray();
        $deficit = $requestedCount - count($collectedIds);
        $seenQuestions = collect();
        if ($deficit > 0 && !empty($seenQuestionIds)) {
            $seenQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $types)
                ->whereIn('id', $seenQuestionIds)
                ->whereNotIn('id', $collectedIds)
                // Join to get ordering data from user_seen_questions
                ->join('user_seen_questions as usq', function ($join) use ($user) {
                    $join->on('question_bank.id', '=', 'usq.question_id')
                         ->where('usq.user_id', '=', $user->id);
                })
                ->orderBy('usq.seen_count', 'asc')       // least seen first
                ->orderBy('usq.last_seen_at', 'asc');    // oldest seen first
            if ($resolvedTopic) {
                $seenQuery->where('question_bank.topic', $resolvedTopic);
            }
            $seenQuestions = $seenQuery->limit($deficit)->get(['question_bank.id']);
        }

        // ── Step 3: Still not enough? Expand beyond topic (type still respected) ──
        $collectedIds = array_merge($collectedIds, $seenQuestions->pluck('id')->toArray());
        $stillNeeded = $requestedCount - count($collectedIds);
        $extraQuestions = collect();
        if ($stillNeeded > 0 && $resolvedTopic) {
            // Try unseen questions from the same type, any topic
            $extraUnseenQuery = QuestionBank::query()
                ->where('is_active', true)
                ->whereIn('question_type', $types)
                ->whereNotIn('id', array_merge($collectedIds, $seenQuestionIds));
            $extraUnseen = $extraUnseenQuery->inRandomOrder()->limit($stillNeeded)->get(['id']);

            $extraQuestions = $extraQuestions->concat($extraUnseen);
            $stillNeeded -= $extraUnseen->count();

            // Final fallback: least-recently-seen from any topic (type still respected)
            if ($stillNeeded > 0 && !empty($seenQuestionIds)) {
                $allCollected = array_merge($collectedIds, $extraUnseen->pluck('id')->toArray());
                $extraSeenQuery = QuestionBank::query()
                    ->where('is_active', true)
                    ->whereIn('question_type', $types)
                    ->whereIn('id', $seenQuestionIds)
                    ->whereNotIn('id', $allCollected)
                    ->join('user_seen_questions as usq', function ($join) use ($user) {
                        $join->on('question_bank.id', '=', 'usq.question_id')
                             ->where('usq.user_id', '=', $user->id);
                    })
                    ->orderBy('usq.seen_count', 'asc')
                    ->orderBy('usq.last_seen_at', 'asc');
                $extraQuestions = $extraQuestions->concat(
                    $extraSeenQuery->limit($stillNeeded)->get(['question_bank.id'])
                );
            }
        }

        // Merge all collected questions
        $questions = $unseenQuestions->concat($seenQuestions)->concat($extraQuestions);

        $count = $questions->count();
        $topic = $resolvedTopic ?? $request->input('topic', 'General Knowledge');
        $level = $user->level ?? '100';

        $questions = $questions->shuffle();
        
        $actualCount = $questions->count();
        if ($actualCount < 1) {
            $msg = 'The questions are not available, please try another course.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 400);
            }
            return back()->with('error', $msg);
        }

        // Calculate exact cost dynamically based on actual returned questions
        $freeQuestionsUsed = min($actualCount, $freeAvailable);
        $paidQuestionsCount = max(0, $actualCount - $freeAvailable);

        $exactTokenCost = 0;
        if ($paidQuestionsCount > 0) {
            // Fetch the specific questions that were returned
            $fetchedQuestions = QuestionBank::whereIn('id', $questions->pluck('id'))->get();
            
            $paidQuestions = $fetchedQuestions->take($paidQuestionsCount);
            
            foreach ($paidQuestions as $q) {
                if ($q->question_type === 'mcq') {
                    $exactTokenCost += 1.0;
                } else {
                    // essay, fill_in, short_answer, theory
                    $exactTokenCost += 1.5;
                }
            }
            
            // Deduct exactly the calculated tokens
            $user->tokens -= $exactTokenCost;
            $user->free_questions_remaining -= $freeQuestionsUsed;
        } else {
            // They didn't pay anything, just subtract from free remaining
            $user->free_questions_remaining -= $freeQuestionsUsed;
        }

        $user->save();

        $session = UserPracticeSession::create([
            'user_id' => $user->id,
            'topic' => $topic,
            'questions' => $questions->pluck('id')->toArray(),
        ]);

        if ($request->wantsJson()) {
            $dbQuestions = QuestionBank::whereIn('id', $session->questions)->with('options')->get()->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values();
            
            $formattedQuestions = $dbQuestions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'q_type' => $q->question_type,
                    'question_text' => $q->question_text,
                    'option_a' => $q->options->where('option_key', 'A')->first()?->option_text,
                    'option_b' => $q->options->where('option_key', 'B')->first()?->option_text,
                    'option_c' => $q->options->where('option_key', 'C')->first()?->option_text,
                    'option_d' => $q->options->where('option_key', 'D')->first()?->option_text,
                    'option_e' => $q->options->where('option_key', 'E')->first()?->option_text,
                ];
            });

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

        $questions = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
            ->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
            ->map(function ($q) {
            return [
                'id' => $q->id,
                'q_type' => $q->question_type,
                'question_text' => $q->question_text,
                'option_a' => $q->options->where('option_key', 'A')->first()?->option_text,
                'option_b' => $q->options->where('option_key', 'B')->first()?->option_text,
                'option_c' => $q->options->where('option_key', 'C')->first()?->option_text,
                'option_d' => $q->options->where('option_key', 'D')->first()?->option_text,
                'option_e' => $q->options->where('option_key', 'E')->first()?->option_text,
            ];
        });

        return Inertia::render('Student/Practice/Take', [
            'practiceSession' => $session,
            'questions' => $questions,
        ]);
    }

    private function gradeNormally($questions, $responses)
    {
        $score = 0;
        $total = count($questions);
        $formattedResponses = [];

        foreach ($questions as $q) {
            $userAnswer = $responses[$q->id] ?? null;
            $isCorrect = false;

            if ($userAnswer && strtolower(trim($userAnswer)) === strtolower(trim($q->correct_answer))) {
                $isCorrect = true;
                $score++;
            }

            $formattedResponses[$q->id] = [
                'user_answer' => $userAnswer,
                'correct_answer' => $q->correct_answer,
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

        $graded = $this->gradeWithAiSynchronously($questions, $responses);

        $session->update([
            'responses'    => $graded['responses'],
            'score'        => $graded['score'],
            'completed_at' => now(),
        ]);

        // ── Record completed questions in the seen-question tracker ──
        // Use upsert so repeated sessions increment the count instead of failing.
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
                ['user_id', 'question_id'],                     // unique keys
                ['seen_count' => DB::raw('seen_count + 1'),    // increment on conflict
                 'last_seen_at' => $now,
                 'updated_at'   => $now]
            );
        }

        if ($request->wantsJson()) {
            $questionsForResults = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
                ->sortBy(function ($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
                ->map(function ($q) {
                    $q->correct_option = $q->correct_answer;
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

    private function gradeWithAiSynchronously($questions, $responses)
    {
        $openai = config('marking.openai');
        if (empty($openai['key'])) {
            return $this->gradeNormally($questions, $responses);
        }

        $promptData = [];
        $indexedQuestions = [];
        foreach ($questions as $q) {
            $userAnswerRaw = $responses[$q->id] ?? null;
            $userAnswerText = $userAnswerRaw;
            $correctText = $q->correct_answer;
            
            if ($q->question_type === 'mcq' && $userAnswerRaw) {
                $letter = strtolower($userAnswerRaw);
                $userAnswerText = "Option " . strtoupper($letter) . ": " . ($q->options->where('option_key', strtoupper($letter))->first()?->option_text ?? '');
                
                $correctLetter = strtolower(substr(trim($q->correct_answer), 0, 1));
                $correctText = "Option " . strtoupper($correctLetter) . ": " . ($q->options->where('option_key', strtoupper($correctLetter))->first()?->option_text ?? $q->correct_answer);
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
                ->timeout(60)
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
                $jsonStr = trim(preg_replace('/^```json|```$/i', '', trim($content)));
                $results = json_decode($jsonStr, true);
                
                if (is_array($results)) {
                    $formattedResponses = [];
                    $correctCount = 0;
                    $total = count($questions);
                    
                    foreach ($results as $res) {
                        $qId = $res['id'];
                        $isCorrect = $res['is_correct'] ?? false;
                        $marks = $isCorrect ? 1 : 0;
                        $correctCount += $marks;
                        
                        $formattedResponses[$qId] = [
                            'user_answer' => $responses[$qId] ?? null,
                            'correct_answer' => $indexedQuestions[$qId]->correct_answer ?? null,
                            'is_correct' => $isCorrect,
                            'marks' => $marks,
                            'ai_feedback' => $res['feedback'] ?? ''
                        ];
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

        return $this->gradeNormally($questions, $responses);
    }

    public function result(UserPracticeSession $session)
    {
        if ($session->user_id !== auth()->id() || $session->completed_at === null) {
            abort(403);
        }

        $questions = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
            ->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
            ->map(function($q) {
            $q->correct_option = $q->correct_answer;
            return $q;
        });

        return Inertia::render('Student/Practice/Result', [
            'practiceSession' => $session,
            'questions' => $questions,
        ]);
    }
}
