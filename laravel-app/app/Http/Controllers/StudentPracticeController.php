<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\UserPracticeSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\AiQuestionGenerationService;

class StudentPracticeController extends Controller
{
    public function generate(Request $request)
    {
        $user = $request->user();

        $requestedCount = min(30, max(5, (int) $request->input('count', 20)));

        // Calculate worst-case token cost before generating
        $freeAvailable = max(0, $user->free_questions_remaining);
        $paidQuestionsCount = max(0, $requestedCount - $freeAvailable);
        $maxTokenCost = $paidQuestionsCount * 1.5;

        if ($paidQuestionsCount > 0 && $user->tokens < $maxTokenCost) {
            $msg = "You've reached the end of your free tier! Top up your tokens to keep unlocking new questions and enjoying your learning journey.";
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return back()->with('error', $msg);
        }

        // Fetch up to 20 random questions from QuestionBank
        $query = QuestionBank::query()->where('is_active', true);
        
        if ($request->filled('topic')) {
            $query->where('topic', 'like', '%' . $request->topic . '%');
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        $questions = $query->inRandomOrder()->limit($requestedCount)->get(['id']);
        
        $count = $questions->count();
        $topic = $request->input('topic', 'General Knowledge');
        $difficulty = $request->input('difficulty', 'medium');
        $level = $user->level ?? '100';

        if ($count < $requestedCount) {
            $deficit = $requestedCount - $count;
            
            // Always use all 4 types
            $types = ['mcq', 'fill_in', 'short_answer', 'theory'];

            $aiService = new AiQuestionGenerationService();
            $generated = $aiService->generateFromTopic(
                $topic,
                $deficit,
                $types,
                $difficulty,
                $deficit * 2, // 2 marks per question
                $level
            );
            
            foreach ($generated as $g) {
                // Save to QuestionBank
                $newQb = QuestionBank::create([
                    'lecturer_id' => $user->id, // Default to current user since it's an auto-generated practice question
                    'topic' => $topic,
                    'question_text' => $g['question_text'],
                    'question_type' => $g['question_type'],
                    'difficulty' => $g['difficulty'],
                    'correct_answer' => $g['correct_answer'],
                    'model_answer' => $g['model_answer'],
                    'marking_scheme' => $g['marking_scheme'],
                    'suggested_marks' => $g['suggested_marks'],
                    'generated_by_ai' => true,
                    'status' => 'approved',
                    'visibility' => 'personal',
                    'created_by' => $user->id,
                ]);

                // Create options if MCQ
                if ($g['question_type'] === 'mcq' && isset($g['options'])) {
                    foreach ($g['options'] as $opt) {
                        QuestionOption::create([
                            'question_bank_id' => $newQb->id,
                            'option_key' => $opt['key'],
                            'option_text' => $opt['text'],
                            'is_correct' => $opt['is_correct'],
                        ]);
                    }
                }
                
                $questions->push($newQb);
            }
        }
        
        $questions = $questions->shuffle();
        
        if ($questions->count() < 1) {
            $msg = 'Unable to generate questions at this time. Please try another topic.';
            if ($request->wantsJson()) {
                return response()->json(['error' => $msg], 400);
            }
            return back()->with('error', $msg);
        }

        // Calculate exact cost dynamically based on question types
        $exactTokenCost = 0;
        if ($paidQuestionsCount > 0) {
            // Fetch the specific questions that were generated/returned
            $fetchedQuestions = QuestionBank::whereIn('id', $questions->pluck('id'))->get();
            
            // To be perfectly fair, we assume the first $freeAvailable questions are the free ones.
            // But we don't care which ones are free, we just take $paidQuestionsCount randomly or just count total and prorate? 
            // Or we just calculate the cost of all questions, and then only charge for a proportional amount? 
            // Actually, we can just sort by type cost, or sort randomly. Let's just take the first $paidQuestionsCount.
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
            $user->free_questions_remaining = 0;
        } else {
            // They didn't pay anything, just subtract from free remaining
            $user->free_questions_remaining -= $requestedCount;
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
            'score' => $total > 0 ? ($score / $total) * 100 : 0,
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
            'responses' => $graded['responses'],
            'score' => $graded['score'],
            'completed_at' => now(),
        ]);

        if ($request->wantsJson()) {
            $questionsForResults = QuestionBank::whereIn('id', $session->questions)->with('options')->get()
                ->sortBy(function($q) use ($session) { return array_flip($session->questions)[$q->id] ?? 9999; })->values()
                ->map(function($q) {
                $q->correct_option = $q->correct_answer;
                return $q;
            });
            return response()->json([
                'practiceSession' => $session,
                'questions' => $questionsForResults,
                'message' => 'Practice submitted successfully! Here is your NovaGrade feedback.'
            ]);
        }

        return redirect()->route('student.practice.result', $session->id)->with('success', 'Practice submitted successfully! Here is your NovaGrade feedback.');
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
                    $score = 0;
                    $total = count($questions);
                    
                    foreach ($results as $res) {
                        $qId = $res['id'];
                        $isCorrect = $res['is_correct'] ?? false;
                        $marks = $res['marks'] ?? ($isCorrect ? 1 : 0);
                        $score += $marks;
                        
                        $formattedResponses[$qId] = [
                            'user_answer' => $responses[$qId] ?? null,
                            'correct_answer' => $indexedQuestions[$qId]->correct_answer ?? null,
                            'is_correct' => $isCorrect,
                            'marks' => $marks,
                            'ai_feedback' => $res['feedback'] ?? ''
                        ];
                    }
                    
                    return [
                        'score' => $total > 0 ? ($score / $total) * 100 : 0,
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
