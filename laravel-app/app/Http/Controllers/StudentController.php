<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use App\Models\UserPracticeSession;
use App\Models\PracticeQuizAttempt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use App\Models\Transaction;

class StudentController extends Controller
{
    public function wallet(Request $request)
    {
        return Inertia::render('Student/Wallet', [
            'tokenPrice' => AppSetting::getValue('token_price_per_question', '1'),
            'activeGateway' => AppSetting::getValue('active_payment_gateway', 'paystack'),
        ]);
    }

    public function recharge(Request $request)
    {
        $user = $request->user();
        $user->tokens += 50;
        $user->save();
        
        return back()->with('success', 'Successfully recharged 50 tokens.');
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        $customSessions = UserPracticeSession::where('user_id', $user->id)
            ->latest('completed_at')
            ->take(3)
            ->get();

        $quizAttempts = PracticeQuizAttempt::with('quiz:id,title')
            ->where('student_email', $user->email)
            ->latest('submitted_at')
            ->take(3)
            ->get();

        return Inertia::render('Student/Dashboard', [
            'walletBalance' => $user->tokens ?? 0,
            'recentSessions' => $customSessions,
            'recentAttempts' => $quizAttempts,
            'freeQuestionsUsed' => $user->free_practice_questions_used ?? 0,
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $customSessions = UserPracticeSession::where('user_id', $user->id)
            ->latest('completed_at')
            ->get();

        $quizAttempts = PracticeQuizAttempt::with('quiz:id,title,course_name,course_code')
            ->where('student_email', $user->email)
            ->latest('submitted_at')
            ->get();

        // Analytics Calculation
        $completedSessions = $customSessions->filter(fn($s) => $s->completed_at !== null);
        $completedAttempts = $quizAttempts->filter(fn($a) => $a->submitted_at !== null);

        $totalQuestionsSolved = 0;
        $weakTopicsCounts = [];
        $progressDataRaw = [];

        // Aggregate data from custom sessions
        foreach ($completedSessions as $session) {
            $questionsCount = is_array($session->questions) ? count($session->questions) : 0;
            $totalQuestionsSolved += $questionsCount;

            if ($session->score !== null && $questionsCount > 0) {
                // Assuming score is correctly calculated out of total marks. 
                // Or wait, score could be something like 5 out of 10.
                // We'll calculate a percentage assuming each question is e.g., 2 marks, or just use score if it is a percentage?
                // Actually the score in custom session is marks obtained. 
                $percentage = ($session->score / ($questionsCount * 2)) * 100;
                
                $progressDataRaw[] = [
                    'timestamp' => $session->completed_at->timestamp,
                    'date' => $session->completed_at->format('M d'),
                    'score' => (float) min(100, $percentage), // cap at 100
                    'topic' => $session->topic ?? 'Custom Practice'
                ];
            }

            $responses = is_array($session->responses) ? $session->responses : [];
            if (!empty($responses) && !empty($session->questions)) {
                $questions = \App\Models\QuestionBank::whereIn('id', $session->questions)->get()->keyBy('id');
                foreach ($responses as $qId => $resp) {
                    $isCorrect = $resp['is_correct'] ?? false;
                    if (!$isCorrect && isset($questions[$qId])) {
                        $topic = $questions[$qId]->topic;
                        if ($topic) {
                            $weakTopicsCounts[$topic] = ($weakTopicsCounts[$topic] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        // Aggregate data from official quizzes
        foreach ($completedAttempts as $attempt) {
            $responsesCount = is_array($attempt->responses) ? count($attempt->responses) : 0;
            $totalQuestionsSolved += $responsesCount;

            if ($attempt->total_marks > 0) {
                $percentage = ($attempt->score / $attempt->total_marks) * 100;
                $progressDataRaw[] = [
                    'timestamp' => $attempt->submitted_at->timestamp,
                    'date' => $attempt->submitted_at->format('M d'),
                    'score' => (float) min(100, $percentage),
                    'topic' => $attempt->quiz->title ?? 'Official Quiz'
                ];
            }
        }

        // Sort progress data chronologically
        usort($progressDataRaw, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        // Take the last 15 attempts for the chart to keep it clean
        $progressData = array_slice($progressDataRaw, -15);

        // Sort weak topics by frequency
        arsort($weakTopicsCounts);
        $topWeakTopics = array_slice(array_keys($weakTopicsCounts), 0, 5);

        // Average Score
        $totalScores = array_sum(array_column($progressDataRaw, 'score'));
        $averageScore = count($progressDataRaw) > 0 ? round($totalScores / count($progressDataRaw), 1) : 0;

        return Inertia::render('Student/History', [
            'customSessions' => $customSessions,
            'quizAttempts' => $quizAttempts,
            'analytics' => [
                'totalQuestionsSolved' => $totalQuestionsSolved,
                'averageScore' => $averageScore,
                'progressData' => $progressData,
                'weakTopics' => $topWeakTopics,
            ]
        ]);
    }

    public function initializePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $gateway = AppSetting::getValue('active_payment_gateway', 'paystack');
        $amount = $request->amount;
        $user = $request->user();
        
        $callbackUrl = route('student.wallet.topup.callback');

        if ($gateway === 'paystack') {
            $secretKey = AppSetting::getValue('paystack_secret_key');
            
            // Add a hidden 2% charge
            $chargeableAmount = $amount * 1.02;
            
            // Generate a unique reference to track this specific attempt
            $reference = uniqid('ngx_') . '_' . time();

            // Store the pending transaction
            Transaction::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $amount, // Base amount they requested
                'gateway' => 'paystack',
                'status' => 'pending',
                'tokens' => 0, // Calculated on success
            ]);

            $httpRequest = Http::withToken($secretKey);
            if (app()->environment('local')) {
                $httpRequest = $httpRequest->withoutVerifying();
            }

            $response = $httpRequest->post('https://api.paystack.co/transaction/initialize', [
                'email' => $user->email,
                'amount' => (int) round($chargeableAmount * 100), // Paystack uses kobo
                'reference' => $reference,
                'callback_url' => $callbackUrl,
            ]);

            if ($response->successful()) {
                return response()->json(['authorization_url' => $response['data']['authorization_url']]);
            }
        } elseif ($gateway === 'moolre') {
            $reference = 'test_moolre_' . uniqid();
            Transaction::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $amount,
                'gateway' => 'moolre',
                'status' => 'pending',
            ]);
            return response()->json(['authorization_url' => $callbackUrl . '?reference=' . $reference]);
        }

        return response()->json(['error' => 'Payment initialization failed'], 400);
    }

    public function paymentCallback(Request $request)
    {
        $gateway = AppSetting::getValue('active_payment_gateway', 'paystack');
        $reference = $request->query('reference') ?? $request->query('trxref');
        
        if (!$reference) {
            return redirect()->route('student.wallet')->with('error', 'Invalid payment reference.');
        }
        
        $transaction = Transaction::where('reference', $reference)->first();
        if (!$transaction) {
            return redirect()->route('student.wallet')->with('error', 'Transaction record not found.');
        }

        if ($transaction->status === 'success') {
            return redirect()->route('student.wallet')->with('success', "Payment was already verified. {$transaction->tokens} tokens added.");
        }

        if ($gateway === 'paystack') {
            $secretKey = AppSetting::getValue('paystack_secret_key');
            
            $httpRequest = Http::withToken($secretKey);
            if (app()->environment('local')) {
                $httpRequest = $httpRequest->withoutVerifying();
            }
            
            $response = $httpRequest->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful() && $response['data']['status'] === 'success') {
                $amountPaidWithCharge = $response['data']['amount'] / 100;
                $baseAmount = $amountPaidWithCharge / 1.02;
                
                $tokenPrice = (float) AppSetting::getValue('token_price_per_question', '1');
                $tokensBought = (int) round($baseAmount / $tokenPrice);

                $user = $request->user();
                $user->tokens += $tokensBought;
                $user->save();
                
                $transaction->update([
                    'status' => 'success',
                    'tokens' => $tokensBought,
                ]);

                return redirect()->route('student.wallet')->with('success', "Payment successful! {$tokensBought} tokens added.");
            } else {
                $transaction->update(['status' => 'failed']);
            }
        } elseif ($gateway === 'moolre') {
            // Mock Moolre verification
            $tokenPrice = (float) AppSetting::getValue('token_price_per_question', '1');
            $tokensBought = (int) round($transaction->amount / $tokenPrice);

            $user = $request->user();
            $user->tokens += $tokensBought;
            $user->save();

            $transaction->update([
                'status' => 'success',
                'tokens' => $tokensBought,
            ]);

            return redirect()->route('student.wallet')->with('success', "Payment successful! {$tokensBought} tokens added.");
        }

        return redirect()->route('student.wallet')->with('error', 'Payment verification failed.');
    }

    public function verifyPayment(Request $request)
    {
        $user = $request->user();
        
        $pendingTransactions = Transaction::where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        if ($pendingTransactions->isEmpty()) {
            return back()->with('error', 'You have no pending payments to verify.');
        }

        $gateway = AppSetting::getValue('active_payment_gateway', 'paystack');
        $successCount = 0;
        $tokensAdded = 0;

        if ($gateway === 'paystack') {
            $secretKey = AppSetting::getValue('paystack_secret_key');
            $tokenPrice = (float) AppSetting::getValue('token_price_per_question', '1');

            foreach ($pendingTransactions as $transaction) {
                $httpRequest = Http::withToken($secretKey);
                if (app()->environment('local')) {
                    $httpRequest = $httpRequest->withoutVerifying();
                }

                $response = $httpRequest->get("https://api.paystack.co/transaction/verify/{$transaction->reference}");

                if ($response->successful() && $response['data']['status'] === 'success') {
                    $amountPaidWithCharge = $response['data']['amount'] / 100;
                    $baseAmount = $amountPaidWithCharge / 1.02;
                    $tokensBought = (int) round($baseAmount / $tokenPrice);

                    $user->tokens += $tokensBought;
                    
                    $transaction->update([
                        'status' => 'success',
                        'tokens' => $tokensBought,
                    ]);

                    $successCount++;
                    $tokensAdded += $tokensBought;
                } elseif ($response->successful() && in_array($response['data']['status'], ['failed', 'abandoned', 'reversed'])) {
                    $transaction->update(['status' => 'failed']);
                }
            }
            $user->save();

            if ($successCount > 0) {
                return back()->with('success', "Successfully verified {$successCount} payment(s)! {$tokensAdded} tokens have been added to your balance.");
            }

            return back()->with('error', 'We checked your pending payments, but none were marked as successful by the payment gateway.');
        }

        return back()->with('error', 'Unable to verify payment for the current gateway.');
    }

    public function settings(Request $request)
    {
        return Inertia::render('Student/Settings', [
            'user' => clone $request->user(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'educational_level' => ['nullable', 'string', 'in:JHS,SHS,Tertiary'],
            'level' => ['nullable', 'string', 'max:50'],
            'profile_picture' => ['nullable', 'image', 'max:2048'], // 2MB max
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $validated['name'];
        if (array_key_exists('educational_level', $validated)) {
            $user->educational_level = $validated['educational_level'];
        }
        if (array_key_exists('level', $validated)) {
            $user->level = $validated['level'];
        }

        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $path = $request->file('profile_picture')->store('avatars', 'public');
            $user->profile_picture = $path;
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('student.settings')->with('success', 'Profile updated successfully.');
    }
}
