<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminUsersController;

use App\Http\Controllers\Admin\InstitutionalIntelligenceController as AdminInstitutionalIntelligenceController;
use App\Http\Controllers\Admin\IntelligenceController as AdminIntelligenceController;
use App\Http\Controllers\Admin\QuestionBankCatalogController;


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\PracticeQuizController;
use App\Http\Controllers\StudentCheatEventController;
use App\Http\Controllers\Student\StudentIntelligenceController;
use App\Http\Controllers\Student\StudentResultController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\StudentExamProctoringController;
use App\Http\Controllers\StudentPracticeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Reports\ExportController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
});

// ── Student exam portal (no staff account; session-based) ───────────────────
Route::get('/student/entry', [StudentExamController::class, 'entryShow'])->name('student.entry');
Route::post('/student/entry', [StudentExamController::class, 'entryStore'])->name('student.entry.store');

Route::prefix('practice-quizzes')->name('practice-quizzes.')->group(function () {
    Route::get('/', [PracticeQuizController::class, 'index'])->name('index');
    Route::get('/{quiz}', [PracticeQuizController::class, 'show'])->name('show');
    Route::post('/{quiz}/start', [PracticeQuizController::class, 'start'])->name('start');
    Route::get('/{quiz}/take', [PracticeQuizController::class, 'take'])->name('take');
    Route::post('/{quiz}/submit', [PracticeQuizController::class, 'submit'])->name('submit');
    Route::get('/{quiz}/results/{attempt}', [PracticeQuizController::class, 'results'])->name('results');
});

Route::get('/student/results', [StudentResultController::class, 'lookupForm'])->name('student.results.lookup-form');
Route::post('/student/results/lookup', [StudentResultController::class, 'lookup'])->name('student.results.lookup');
Route::middleware('student_result_verified')->group(function () {
    Route::get('/student/results/{attempt}', [StudentResultController::class, 'show'])->name('student.results.show');
    Route::get('/student/results/{attempt}/revision-plan', [StudentResultController::class, 'showRevisionPlan'])->name('student.results.revision-plan');
    Route::get('/student/intelligence/{attempt}/learning-twin', [StudentIntelligenceController::class, 'learningTwin'])->name('student.intelligence.learning-twin');
    Route::get('/student/intelligence/{attempt}/academic-health', [StudentIntelligenceController::class, 'academicHealth'])->name('student.intelligence.academic-health');
    Route::get('/student/intelligence/{attempt}/recommendations', [StudentIntelligenceController::class, 'recommendations'])->name('student.intelligence.recommendations');
});

Route::middleware('auth')->prefix('student')->name('student.')->group(function () {
    Route::get('/wallet', [StudentController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/topup/initialize', [StudentController::class, 'initializePayment'])->middleware('throttle:10,1')->name('wallet.topup.initialize');
    Route::get('/wallet/topup/callback', [StudentController::class, 'paymentCallback'])->name('wallet.topup.callback');
    Route::post('/wallet/topup/verify', [StudentController::class, 'verifyPayment'])->name('wallet.topup.verify');
    Route::get('/settings', [StudentController::class, 'settings'])->name('settings');
    Route::get('/history', [StudentController::class, 'history'])->name('history');
    Route::post('/settings', [StudentController::class, 'updateSettings'])->name('settings.update');
    
    Route::post('/practice/generate', [StudentPracticeController::class, 'generate'])->name('practice.generate');
    Route::get('/practice/{session}/take', [StudentPracticeController::class, 'take'])->name('practice.take');
    Route::post('/practice/{session}/submit', [StudentPracticeController::class, 'submit'])->name('practice.submit');
    Route::get('/practice/{session}/result', [StudentPracticeController::class, 'result'])->name('practice.result');
});

Route::middleware('student')->group(function () {
    Route::get('/exam', [StudentExamController::class, 'takeExam'])->name('student.exam');
    Route::get('/exam/verification-required', [StudentExamController::class, 'verificationRequired'])->name('student.verification-required');
    Route::post('/exam/submit', [StudentExamController::class, 'submit'])->name('student.submit');
    Route::post('/exam/autosave', [StudentExamController::class, 'autosave'])->name('student.autosave');
    Route::get('/exam/result', [StudentExamController::class, 'result'])->name('student.result');
    Route::get('/exam/terminated', [StudentExamController::class, 'terminated'])->name('student.terminated');

    Route::post('/proctoring/enable', [StudentExamProctoringController::class, 'enable'])->name('proctoring.enable');
    Route::post('/proctoring/log', [StudentExamProctoringController::class, 'log'])->name('proctoring.log');
    Route::post('/proctoring/capture', [StudentExamProctoringController::class, 'capture'])->name('proctoring.capture');
    Route::post('/proctoring/recording', [StudentExamProctoringController::class, 'saveRecording'])->name('proctoring.recording');
    Route::post('/proctoring/cheat-event', [StudentCheatEventController::class, 'store'])->name('proctoring.cheat-event');
});

Route::get('/', function () {
    $user = auth()->user();
    if (! $user instanceof User) {
        return Inertia::render('Landing/Home');
    }

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->isHod()) {
        return redirect()->route('hod.dashboard');
    }

    if ($user->isStudent()) {
        return redirect()->route('practice-quizzes.index');
    }

    return redirect()->route('practice-quizzes.index');
})->name('home');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/institutional-intelligence', [AdminInstitutionalIntelligenceController::class, 'index'])->name('institutional-intelligence');
    Route::get('/institutional-intelligence/export', [ExportController::class, 'exportInstitutionalAnalytics'])->name('institutional-intelligence.export');
    Route::get('/proctoring/export', [ExportController::class, 'exportProctoringReport'])->name('proctoring.export');
    Route::get('/schools', [AdminController::class, 'schools'])->name('schools');
    Route::get('/schools/create', [AdminController::class, 'createSchool'])->name('schools.create');
    Route::post('/schools', [AdminController::class, 'storeSchool'])->name('schools.store');
    Route::get('/schools/{school}/edit', [AdminController::class, 'editSchool'])->name('schools.edit');
    Route::put('/schools/{school}', [AdminController::class, 'updateSchool'])->name('schools.update');
    Route::delete('/schools/{school}', [AdminController::class, 'destroySchool'])->name('schools.destroy');
    Route::get('/departments', [AdminController::class, 'departments'])->name('departments');
    Route::get('/departments/create', [AdminController::class, 'createDepartment'])->name('departments.create');
    Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('departments.store');
    Route::get('/departments/{department}/edit', [AdminController::class, 'editDepartment'])->name('departments.edit');
    Route::put('/departments/{department}', [AdminController::class, 'updateDepartment'])->name('departments.update');
    Route::delete('/departments/{department}', [AdminController::class, 'destroyDepartment'])->name('departments.destroy');
    Route::get('/exams-overview', [AdminController::class, 'allExams'])->name('exams');
    Route::get('/exams-overview/{exam}', [AdminController::class, 'showExamOverview'])->name('exams.show');
    Route::post('/exams-overview/{exam}/reset-student-attempts', [AdminController::class, 'resetStudentExamAttempts'])->name('exams.reset-student-attempts');
    Route::get('/quizzes', [AdminController::class, 'allQuizzes'])->name('quizzes');
    Route::middleware('admin_permission:manage_students')->group(function () {
        Route::get('/students', [AdminController::class, 'allStudents'])->name('students');
    });
    Route::match(['delete', 'post'], '/students', [AdminController::class, 'deleteStudents'])->name('students.delete');
    Route::put('/students/{student_index}/email', [AdminController::class, 'updateStudentEmail'])
        ->where('student_index', '[^/]+')
        ->name('students.email.update');

    Route::middleware('admin_permission:export_users')->group(function () {
        Route::get('/students/export-emails', [AdminController::class, 'exportEmails'])->name('export.user_emails');
        Route::get('/students/export-phones', [AdminController::class, 'exportPhones'])->name('export.user_phones');
    });

    Route::get('/frequent-cheaters', [AdminController::class, 'frequentCheaters'])->name('frequent-cheaters');
    Route::get('/email-settings', [AdminController::class, 'emailSettings'])->name('email-settings');
    Route::post('/email-settings', [AdminController::class, 'updateEmailSettings'])->name('email-settings.update');
    
    Route::get('/billing-settings', [AdminController::class, 'billingSettings'])->name('billing-settings');
    Route::post('/billing-settings', [AdminController::class, 'updateBillingSettings'])->name('billing-settings.update');

    Route::resource('admins', AdminUsersController::class)->except(['show']);

    Route::get('/intelligence/institutional', [AdminIntelligenceController::class, 'institutional'])->name('intelligence.institutional');
    Route::get('/intelligence/at-risk', [AdminIntelligenceController::class, 'atRisk'])->name('intelligence.at-risk');
    Route::get('/intelligence/department', [AdminIntelligenceController::class, 'department'])->name('intelligence.department');
    Route::get('/intelligence/cohort', [AdminIntelligenceController::class, 'cohort'])->name('intelligence.cohort');
    Route::post('/intelligence/analytics/generate', [AdminIntelligenceController::class, 'analyticsGenerate'])->name('intelligence.analytics.generate');
});

Route::middleware(['auth', 'approved_staff', 'role:admin,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/question-bank-catalog', [QuestionBankCatalogController::class, 'index'])->name('question-bank-catalog.index');
    Route::get('/question-bank-catalog/import', [QuestionBankCatalogController::class, 'importForm'])->name('question-bank-catalog.import');
    Route::post('/question-bank-catalog/import', [QuestionBankCatalogController::class, 'importStore'])->name('question-bank-catalog.import.store');
    Route::get('/question-bank-catalog/import/template', [QuestionBankCatalogController::class, 'downloadTemplate'])->name('question-bank-catalog.import.template');
    Route::get('/question-bank-catalog/ai-generate', [QuestionBankCatalogController::class, 'aiGenerateForm'])->name('question-bank-catalog.ai-generate');
    Route::post('/question-bank-catalog/ai-generate', [QuestionBankCatalogController::class, 'aiGenerateStore'])->middleware('throttle:10,1')->name('question-bank-catalog.ai-generate.store');
    
    Route::get('/question-bank-catalog/{question}/edit', [QuestionBankCatalogController::class, 'edit'])->name('question-bank-catalog.edit');
    Route::put('/question-bank-catalog/{question}', [QuestionBankCatalogController::class, 'update'])->name('question-bank-catalog.update');
    Route::delete('/question-bank-catalog/{question}', [QuestionBankCatalogController::class, 'destroy'])->name('question-bank-catalog.destroy');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
use Illuminate\Support\Facades\Artisan;

Route::get('/run-migrations', function () {
    Artisan::call('migrate', ['--force' => true]);
    return "Migrations completed successfully!";
});

