<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'test688@example.com')->first();
$sessions = App\Models\UserPracticeSession::where('user_id', $user->id)->get();

$allQuestionIds = [];
foreach ($sessions as $session) {
    if (is_array($session->questions)) {
        $allQuestionIds = array_merge($allQuestionIds, $session->questions);
    }
}

$questions = App\Models\QuestionBank::whereIn('id', $allQuestionIds)->get();

$mcqCount = 0;
$otherCount = 0;

foreach ($questions as $q) {
    if ($q->question_type === 'mcq') {
        $mcqCount++;
    } else {
        $otherCount++;
    }
}

$totalQuestions = $mcqCount + $otherCount;
echo "Total Questions: {$totalQuestions}\n";

$freeRemaining = 20;
$paidMcq = 0;
$paidOther = 0;

if ($freeRemaining >= $mcqCount) {
    $freeRemaining -= $mcqCount;
    $paidMcq = 0;
} else {
    $paidMcq = $mcqCount - $freeRemaining;
    $freeRemaining = 0;
}

if ($freeRemaining >= $otherCount) {
    $freeRemaining -= $otherCount;
    $paidOther = 0;
} else {
    $paidOther = $otherCount - $freeRemaining;
    $freeRemaining = 0;
}

$exactCost = ($paidMcq * 1) + ($paidOther * 1.5);
echo "Exact Cost: {$exactCost}\n";

$newBalance = 50 - $exactCost;
$user->tokens = $newBalance;
$user->free_questions_remaining = $freeRemaining;
$user->save();

echo "New Balance: {$newBalance}\n";
echo "Free Remaining: {$freeRemaining}\n";
