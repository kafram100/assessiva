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
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function wallet(Request $request)
    {
        return Inertia::render('Student/Wallet', [
            'tokenPrice' => AppSetting::getValue('token_price_per_question', '1'),
            'activeGateway' => AppSetting::getValue('active_payment_gateway', 'paystack'),
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
                // Score in UserPracticeSession is already stored as a percentage (0 to 100)
                $percentage = (float) $session->score;
                
                $progressDataRaw[] = [
                    'timestamp' => $session->completed_at->timestamp,
                    'date' => $session->completed_at->format('M d'),
                    'score' => (float) min(100, max(0, $percentage)),
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
            return response()->json(['error' => 'Moolre payments are disabled until the live integration is configured. Select Paystack.'], 400);
        }

        return response()->json(['error' => 'Payment initialization failed'], 400);
    }

    public function paymentCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');
        
        if (!$reference) {
            return redirect()->route('student.wallet')->with('error', 'Invalid payment reference.');
        }
        
        $transaction = Transaction::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$transaction) {
            return redirect()->route('student.wallet')->with('error', 'Transaction record not found.');
        }

        if ($transaction->status === 'success') {
            return redirect()->route('student.wallet')->with('success', "Payment was already verified. {$transaction->tokens} tokens added.");
        }

        $gateway = $transaction->gateway;

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
                $tokensBought = (int) round($baseAmount / max(0.01, $tokenPrice));

                DB::transaction(function () use ($transaction, $request, $tokensBought) {
                    $lockedTx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if ($lockedTx && $lockedTx->status !== 'success') {
                        $lockedUser = User::where('id', $request->user()->id)->lockForUpdate()->first();
                        if ($lockedUser) {
                            $lockedUser->tokens += $tokensBought;
                            $lockedUser->save();
                        }
                        $lockedTx->update([
                            'status' => 'success',
                            'tokens' => $tokensBought,
                        ]);
                    }
                });

                return redirect()->route('student.wallet')->with('success', "Payment successful! {$tokensBought} tokens added.");
            } else {
                $transaction->update(['status' => 'failed']);
            }
        } elseif ($gateway === 'moolre') {
            return redirect()->route('student.wallet')->with('error', 'Moolre payments are disabled until the live integration is configured.');
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

        $successCount = 0;
        $tokensAdded = 0;

        $paystackTransactions = $pendingTransactions->where('gateway', 'paystack');
        if ($paystackTransactions->isEmpty()) {
            return back()->with('error', 'There are no pending Paystack payments to verify.');
        }

        $secretKey = AppSetting::getValue('paystack_secret_key');
        $tokenPrice = (float) AppSetting::getValue('token_price_per_question', '1');

        foreach ($paystackTransactions as $transaction) {
            $httpRequest = Http::withToken($secretKey);
            if (app()->environment('local')) {
                $httpRequest = $httpRequest->withoutVerifying();
            }

            $response = $httpRequest->get("https://api.paystack.co/transaction/verify/{$transaction->reference}");

            if ($response->successful() && $response['data']['status'] === 'success') {
                $amountPaidWithCharge = $response['data']['amount'] / 100;
                $baseAmount = $amountPaidWithCharge / 1.02;
                $tokensBought = (int) round($baseAmount / max(0.01, $tokenPrice));

                $credited = DB::transaction(function () use ($transaction, $user, $tokensBought) {
                    $lockedTx = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
                    if ($lockedTx && $lockedTx->status !== 'success') {
                        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                        if ($lockedUser) {
                            $lockedUser->tokens += $tokensBought;
                            $lockedUser->save();
                        }
                        $lockedTx->update([
                            'status' => 'success',
                            'tokens' => $tokensBought,
                        ]);
                        return true;
                    }
                    return false;
                });

                if ($credited) {
                    $successCount++;
                    $tokensAdded += $tokensBought;
                }
            } elseif ($response->successful() && in_array($response['data']['status'], ['failed', 'abandoned', 'reversed'])) {
                $transaction->update(['status' => 'failed']);
            }
        }

        if ($successCount > 0) {
            return back()->with('success', "Successfully verified {$successCount} payment(s)! {$tokensAdded} tokens have been added to your balance.");
        }

        return back()->with('error', 'We checked your pending Paystack payments, but none were marked as successful by the payment gateway.');
    }

    public function paystackWebhook(Request $request)
    {
        $secretKey = AppSetting::getValue('paystack_secret_key');
        if (blank($secretKey)) {
            $secretKey = config('services.paystack.secret_key') ?: env('PAYSTACK_SECRET_KEY');
        }
        if (blank($secretKey)) {
            return response()->json(['error' => 'Not configured'], 400);
        }

        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (empty($signature) || hash_hmac('sha512', $payload, $secretKey) !== $signature) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (($event['event'] ?? '') === 'charge.success') {
            $data = $event['data'] ?? [];
            $reference = $data['reference'] ?? null;

            if ($reference) {
                DB::transaction(function () use ($reference, $data) {
                    $transaction = Transaction::where('reference', $reference)
                        ->lockForUpdate()
                        ->first();

                    if ($transaction && $transaction->status !== 'success') {
                        $amountPaidWithCharge = ($data['amount'] ?? 0) / 100;
                        $baseAmount = $amountPaidWithCharge / 1.02;
                        $tokenPrice = (float) AppSetting::getValue('token_price_per_question', '1');
                        $tokensBought = (int) round($baseAmount / max(0.01, $tokenPrice));

                        $user = User::where('id', $transaction->user_id)->lockForUpdate()->first();
                        if ($user) {
                            $user->tokens += $tokensBought;
                            $user->save();
                        }

                        $transaction->update([
                            'status' => 'success',
                            'tokens' => $tokensBought,
                        ]);
                    }
                });
            }
        }

        return response()->json(['status' => 'ok']);
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
            'name' => ['required', 'string', 'max:255'],
            'educational_level' => ['nullable', 'string', 'in:JHS,SHS,Tertiary'],
            'level' => ['nullable', 'string', 'max:50'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:10240'], // 10MB max
            'remove_picture' => ['nullable', 'boolean'],
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

        if (!empty($validated['remove_picture'])) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
                $user->profile_picture = null;
            }
        } elseif ($request->hasFile('profile_picture')) {
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

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:10240'],
        ]);

        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $request->file('profile_picture')->store('avatars', 'public');
        $user->profile_picture = $path;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'profile_picture_url' => $user->profile_picture_url,
                'user' => $user->fresh(),
            ]);
        }

        return redirect()->route('student.settings')->with('success', 'Profile picture updated successfully.');
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $user->profile_picture = null;
            $user->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture removed successfully.',
                'profile_picture_url' => null,
                'user' => $user->fresh(),
            ]);
        }

        return redirect()->route('student.settings')->with('success', 'Profile picture removed successfully.');
    }
}
