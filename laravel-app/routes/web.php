<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\HodManagementController;
use App\Http\Controllers\Admin\InstitutionalIntelligenceController as AdminInstitutionalIntelligenceController;
use App\Http\Controllers\Admin\IntelligenceController as AdminIntelligenceController;
use App\Http\Controllers\Admin\OfficialQuestionReviewController;
use App\Http\Controllers\Admin\QuestionBankCatalogController;
use App\Http\Controllers\Auth\LecturerRegisterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Hod\AcademicIntelligenceController as HodAcademicIntelligenceController;
use App\Http\Controllers\Hod\AiQuestionBankSettingsController;
use App\Http\Controllers\Hod\AiQuestionReviewController as HodAiQuestionReviewController;
use App\Http\Controllers\Hod\DashboardController as HodDashboardController;
use App\Http\Controllers\Hod\DepartmentExamsController as HodDepartmentExamsController;
use App\Http\Controllers\Hod\IntelligenceController as HodIntelligenceController;
use App\Http\Controllers\Hod\LecturerDirectoryController as HodLecturerDirectoryController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboardController;
use App\Http\Controllers\Lecturer\EmailSettingsController;
use App\Http\Controllers\Lecturer\ExamController as LecturerExamController;
use App\Http\Controllers\Lecturer\ExamFairnessController;
use App\Http\Controllers\Lecturer\ExamQuestionBankController;
use App\Http\Controllers\Lecturer\GradeController as LecturerGradeController;
use App\Http\Controllers\Lecturer\IntelligenceController as LecturerIntelligenceController;
use App\Http\Controllers\Lecturer\LecturerQuestionBankController;
use App\Http\Controllers\Lecturer\PracticeQuizController as LecturerPracticeQuizController;
use App\Http\Controllers\Lecturer\ProctoringController as LecturerProctoringController;
use App\Http\Controllers\Lecturer\ProctoringPenaltyController;
use App\Http\Controllers\Lecturer\QuestionController as LecturerQuestionController;
use App\Http\Controllers\Lecturer\StudentOverviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\PracticeQuizController;
use App\Http\Controllers\StudentCheatEventController;
use App\Http\Controllers\Student\StudentIntelligenceController;
use App\Http\Controllers\Student\StudentResultController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\StudentExamProctoringController;
use App\Http\Controllers\Lecturer\RevisionPlanController as LecturerRevisionPlanController;
use App\Http\Controllers\Reports\ExportController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('lecturer/register', [LecturerRegisterController::class, 'create'])
        ->name('lecturer.register');
    Route::post('lecturer/register', [LecturerRegisterController::class, 'store'])
        ->name('lecturer.register.store');
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

    return redirect()->route('lecturer.dashboard');
})->name('home');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
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
    Route::get('/lecturers/pending', [AdminController::class, 'pendingLecturers'])->name('lecturers.pending');
    Route::get('/lecturers', [AdminController::class, 'lecturers'])->name('lecturers');
    Route::post('/lecturers/{user}/approve', [AdminController::class, 'approveLecturer'])->name('lecturers.approve');
    Route::post('/lecturers/{user}/reject', [AdminController::class, 'rejectLecturer'])->name('lecturers.reject');
    Route::post('/lecturers/{user}/suspend', [AdminController::class, 'suspendLecturer'])->name('lecturers.suspend');
    Route::post('/lecturers/{user}/promote-to-hod', [HodManagementController::class, 'promoteFromLecturer'])->name('lecturers.promote-to-hod');
    Route::put('/lecturers/{user}/department', [AdminController::class, 'updateStaffDepartment'])->name('lecturers.department.update');
    Route::get('/lecturers/create', [AdminController::class, 'createLecturer'])->name('lecturers.create');
    Route::post('/lecturers', [AdminController::class, 'storeLecturer'])->name('lecturers.store');

    Route::get('/hods', [HodManagementController::class, 'index'])->name('hods.index');
    Route::get('/hods/create', [HodManagementController::class, 'create'])->name('hods.create');
    Route::post('/hods', [HodManagementController::class, 'store'])->name('hods.store');
    Route::post('/hods/{user}/deactivate', [HodManagementController::class, 'deactivate'])->name('hods.deactivate');
    Route::put('/hods/{user}/department', [HodManagementController::class, 'updateDepartment'])->name('hods.department.update');
    Route::get('/exams-overview', [AdminController::class, 'allExams'])->name('exams');
    Route::get('/exams-overview/{exam}', [AdminController::class, 'showExamOverview'])->name('exams.show');
    Route::post('/exams-overview/{exam}/reset-student-attempts', [AdminController::class, 'resetStudentExamAttempts'])->name('exams.reset-student-attempts');
    Route::get('/quizzes', [AdminController::class, 'allQuizzes'])->name('quizzes');
    Route::get('/students', [AdminController::class, 'allStudents'])->name('students');
    Route::put('/students/{student_index}/email', [AdminController::class, 'updateStudentEmail'])
        ->where('student_index', '[^/]+')
        ->name('students.email.update');
    Route::get('/frequent-cheaters', [AdminController::class, 'frequentCheaters'])->name('frequent-cheaters');
    Route::get('/email-settings', [AdminController::class, 'emailSettings'])->name('email-settings');
    Route::post('/email-settings', [AdminController::class, 'updateEmailSettings'])->name('email-settings.update');

    Route::get('/intelligence/institutional', [AdminIntelligenceController::class, 'institutional'])->name('intelligence.institutional');
    Route::get('/intelligence/at-risk', [AdminIntelligenceController::class, 'atRisk'])->name('intelligence.at-risk');
    Route::get('/intelligence/department', [AdminIntelligenceController::class, 'department'])->name('intelligence.department');
    Route::get('/intelligence/cohort', [AdminIntelligenceController::class, 'cohort'])->name('intelligence.cohort');
    Route::post('/intelligence/analytics/generate', [AdminIntelligenceController::class, 'analyticsGenerate'])->name('intelligence.analytics.generate');
});

Route::middleware(['auth', 'approved_staff', 'admin_or_hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/question-bank-catalog', [QuestionBankCatalogController::class, 'index'])->name('question-bank-catalog.index');
    Route::get('/official-question-reviews', [OfficialQuestionReviewController::class, 'index'])->name('official-question-reviews.index');
    Route::post('/official-question-reviews/{official_question_review}/approve', [OfficialQuestionReviewController::class, 'approve'])->name('official-question-reviews.approve');
    Route::post('/official-question-reviews/{official_question_review}/reject', [OfficialQuestionReviewController::class, 'reject'])->name('official-question-reviews.reject');
});

Route::middleware(['auth', 'approved_staff', 'hod'])->prefix('hod')->name('hod.')->group(function () {
    Route::get('/dashboard', HodDashboardController::class)->name('dashboard');
    Route::get('/ai-question-reviews', [HodAiQuestionReviewController::class, 'index'])->name('ai-question-reviews.index');
    Route::post('/ai-question-reviews/bulk-approve', [HodAiQuestionReviewController::class, 'bulkApprove'])->name('ai-question-reviews.bulk-approve');
    Route::post('/ai-question-reviews/bulk-reject', [HodAiQuestionReviewController::class, 'bulkReject'])->name('ai-question-reviews.bulk-reject');
    Route::get('/ai-question-bank-settings', [AiQuestionBankSettingsController::class, 'edit'])->name('ai-question-bank-settings.edit');
    Route::post('/ai-question-bank-settings', [AiQuestionBankSettingsController::class, 'update'])->name('ai-question-bank-settings.update');
    Route::get('/academic-intelligence', [HodAcademicIntelligenceController::class, 'index'])->name('academic-intelligence');
    Route::get('/academic-intelligence/export', [ExportController::class, 'exportDepartmentAnalytics'])->name('academic-intelligence.export');
    Route::get('/lecturers', HodLecturerDirectoryController::class)->name('lecturers.index');
    Route::get('/exams', HodDepartmentExamsController::class)->name('exams.index');

    Route::get('/intelligence/institutional', [HodIntelligenceController::class, 'institutional'])->name('intelligence.institutional');
    Route::get('/intelligence/at-risk', [HodIntelligenceController::class, 'atRisk'])->name('intelligence.at-risk');
    Route::get('/intelligence/department', [HodIntelligenceController::class, 'department'])->name('intelligence.department');
    Route::get('/intelligence/cohort', [HodIntelligenceController::class, 'cohort'])->name('intelligence.cohort');
    Route::post('/intelligence/analytics/generate', [HodIntelligenceController::class, 'analyticsGenerate'])->name('intelligence.analytics.generate');
});

Route::middleware(['auth', 'approved_staff', 'lecturer_area'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', LecturerDashboardController::class)->name('dashboard');
    Route::get('/intelligence/learning-twin', [LecturerIntelligenceController::class, 'learningTwin'])->name('intelligence.learning-twin');
    Route::post('/intelligence/learning-twin', [LecturerIntelligenceController::class, 'learningTwinGenerate'])->name('intelligence.learning-twin.generate');
    Route::get('/intelligence/prediction', [LecturerIntelligenceController::class, 'prediction'])->name('intelligence.prediction');
    Route::post('/intelligence/prediction', [LecturerIntelligenceController::class, 'predictionGenerate'])->name('intelligence.prediction.generate');
    Route::get('/intelligence/analytics', [LecturerIntelligenceController::class, 'analytics'])->name('intelligence.analytics');
    Route::post('/intelligence/analytics/generate', [LecturerIntelligenceController::class, 'analyticsGenerate'])->name('intelligence.analytics.generate');
    Route::get('/courses/roster-template', [CourseController::class, 'rosterTemplate'])->name('courses.roster.template');
    Route::post('/courses/{course}/roster', [CourseController::class, 'rosterStore'])->name('courses.roster.store');
    Route::get('/courses/{course}/roster/export', [CourseController::class, 'rosterExport'])->name('courses.roster.export');
    Route::resource('courses', CourseController::class);
    Route::get('/students', StudentOverviewController::class)->name('students.index');
    Route::get('/settings/email', [EmailSettingsController::class, 'email'])->name('settings.email');
    Route::get('/proctoring/monitor', [LecturerProctoringController::class, 'monitor'])->name('proctoring.monitor');
    Route::get('/proctoring/report', [LecturerProctoringController::class, 'report'])->name('proctoring.report');
    Route::get('/proctoring/export', [ExportController::class, 'exportProctoringReport'])->name('proctoring.export');

    Route::get('/exams/{exam}/proctoring/penalties', [ProctoringPenaltyController::class, 'index'])->name('exams.proctoring.penalties.index');
    Route::post('/exams/{exam}/proctoring/penalties', [ProctoringPenaltyController::class, 'store'])->name('exams.proctoring.penalties.store');
    Route::put('/exams/{exam}/proctoring/penalties/{rule}', [ProctoringPenaltyController::class, 'update'])->name('exams.proctoring.penalties.update');
    Route::post('/exams/{exam}/proctoring/events/{event}/apply-penalty', [ProctoringPenaltyController::class, 'applyToEvent'])->name('exams.proctoring.events.apply-penalty');
    Route::post('/exams/{exam}/proctoring/events/{event}/ignore', [ProctoringPenaltyController::class, 'ignoreEvent'])->name('exams.proctoring.events.ignore');
    Route::post('/exams/{exam}/proctoring/events/{event}/dismiss', [ProctoringPenaltyController::class, 'dismissEvent'])->name('exams.proctoring.events.dismiss');
    Route::post('/exams/{exam}/attempts/{attempt}/proctoring/apply-reviewed-penalties', [ProctoringPenaltyController::class, 'applyAllReviewed'])->name('exams.proctoring.attempts.apply-reviewed');
    Route::get('/exams/{exam}/attempts/{attempt}/penalty-summary', [ProctoringPenaltyController::class, 'penaltySummary'])->name('exams.proctoring.attempts.penalty-summary');

    Route::get('/ai-generate-questions', [LecturerQuestionBankController::class, 'generateForm'])->name('ai-generate');
    Route::post('/ai-generate-questions', [LecturerQuestionBankController::class, 'generateStore'])->name('ai-generate.store');
    Route::get('/ai-generation-requests/{id}', [LecturerQuestionBankController::class, 'reviewRequest'])->name('question-bank.review');
    Route::post('/ai-generation-requests/{id}/approve-all', [LecturerQuestionBankController::class, 'approveAllInAiRequest'])->name('question-bank.review.approve-all');
    Route::post('/ai-generation-requests/{id}/submit-for-hod-review', [LecturerQuestionBankController::class, 'submitAiBatchForHodReview'])->name('question-bank.review.submit-for-hod');
    Route::get('/question-bank', [LecturerQuestionBankController::class, 'index'])->name('question-bank.index');
    Route::post('/question-bank/delete-all', [LecturerQuestionBankController::class, 'destroyAllOwned'])->name('question-bank.delete-all');
    Route::post('/question-bank/bulk-destroy', [LecturerQuestionBankController::class, 'destroyBulk'])->name('question-bank.bulk-destroy');
    Route::get('/question-bank/generate-quiz', [LecturerQuestionBankController::class, 'generateQuizForm'])->name('question-bank.generate-quiz');
    Route::post('/question-bank/generate-quiz', [LecturerQuestionBankController::class, 'generateQuizStore'])->name('question-bank.generate-quiz.store');
    Route::get('/question-bank/analytics', [LecturerQuestionBankController::class, 'analytics'])->name('question-bank.analytics');
    Route::put('/question-bank/{id}', [LecturerQuestionBankController::class, 'updateQuestion'])->name('question-bank.update');
    Route::post('/questions/{id}/approve', [LecturerQuestionBankController::class, 'approveQuestion'])->name('questions.approve');
    Route::post('/questions/{id}/reject', [LecturerQuestionBankController::class, 'rejectQuestion'])->name('questions.reject');
    Route::post('/question-bank/{id}/archive', [LecturerQuestionBankController::class, 'archiveQuestion'])->name('question-bank.archive');
    Route::post('/question-bank/{id}/duplicate', [LecturerQuestionBankController::class, 'duplicateQuestion'])->name('question-bank.duplicate');
    Route::post('/question-bank/{id}/submit-official', [LecturerQuestionBankController::class, 'submitOfficial'])->name('question-bank.submit-official');

    Route::get('/practice-quizzes', [LecturerPracticeQuizController::class, 'index'])->name('practice-quizzes.index');
    Route::get('/practice-quizzes/create', [LecturerPracticeQuizController::class, 'create'])->name('practice-quizzes.create');
    Route::post('/practice-quizzes', [LecturerPracticeQuizController::class, 'store'])->name('practice-quizzes.store');
    Route::get('/practice-quizzes/{quiz}/edit', [LecturerPracticeQuizController::class, 'edit'])->name('practice-quizzes.edit');
    Route::put('/practice-quizzes/{quiz}', [LecturerPracticeQuizController::class, 'update'])->name('practice-quizzes.update');
    Route::post('/practice-quizzes/{quiz}/bank-questions', [LecturerPracticeQuizController::class, 'attachBankQuestions'])->name('practice-quizzes.attach-bank');
    Route::post('/practice-quizzes/{quiz}/publish', [LecturerPracticeQuizController::class, 'publish'])->name('practice-quizzes.publish');
    Route::post('/practice-quizzes/{quiz}/unpublish', [LecturerPracticeQuizController::class, 'unpublish'])->name('practice-quizzes.unpublish');
    Route::get('/practice-quizzes/{quiz}/analytics', [LecturerPracticeQuizController::class, 'analytics'])->name('practice-quizzes.analytics');
    Route::get('/practice-quizzes/{quiz}/analytics/export', [ExportController::class, 'exportPracticeAnalytics'])->name('practice-quizzes.analytics.export');

    Route::get('/exams', [LecturerExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [LecturerExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [LecturerExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/roster-template', [LecturerExamController::class, 'downloadAllowedIndexTemplate'])->name('exams.roster.template');
    Route::post('/exams/{exam}/roster', [LecturerExamController::class, 'rosterStore'])->name('exams.roster.store');
    Route::get('/exams/{exam}/roster/export', [LecturerExamController::class, 'rosterExport'])->name('exams.roster.export');
    Route::post('/exams/{exam}/roster/sync-from-course', [LecturerExamController::class, 'syncRosterFromCourse'])->name('exams.roster.sync-from-course');
    Route::get('/questions/template', [LecturerQuestionController::class, 'downloadTemplate'])->name('questions.template');
    Route::get('/exams/{exam}/questions/create', [LecturerQuestionController::class, 'create'])->name('questions.create');
    Route::post('/exams/{exam}/questions', [LecturerQuestionController::class, 'store'])->name('questions.store');
    Route::get('/exams/{exam}/questions/import', [LecturerQuestionController::class, 'importForm'])->name('questions.import');
    Route::post('/exams/{exam}/questions/import', [LecturerQuestionController::class, 'import'])->name('questions.import.store');
    Route::get('/exams/{exam}/question-bank', [ExamQuestionBankController::class, 'index'])->name('exams.question-bank.index');
    Route::post('/exams/{exam}/question-bank/attach', [ExamQuestionBankController::class, 'attach'])->name('exams.question-bank.attach');
    Route::get('/exams/{exam}/stats', [LecturerGradeController::class, 'stats'])->name('exams.stats');
    Route::get('/exams/{exam}/stats/download', [LecturerGradeController::class, 'downloadResults'])->name('exams.stats.download');
    Route::get('/exams/{exam}/results/export', [ExportController::class, 'exportExamResults'])->name('exams.results.export');
    Route::post('/exams/{exam}/release-results', [LecturerGradeController::class, 'releaseResults'])->name('exams.results.release');
    Route::post('/exams/{exam}/revision-plans/generate-all', [LecturerRevisionPlanController::class, 'generateForExam'])->name('exams.revision-plans.generate-all');
    Route::post('/exams/{exam}/reset-student-attempts', [LecturerGradeController::class, 'resetStudentAttempts'])->name('exams.reset-student-attempts');
    Route::get('/exams/{exam}/fairness', [ExamFairnessController::class, 'show'])->name('exams.fairness.show');
    Route::get('/exams/{exam}/fairness/export', [ExportController::class, 'exportFairnessReport'])->name('exams.fairness.export');
    Route::get('/exams/{exam}/learning-twins/export', [ExportController::class, 'exportLearningTwin'])->name('exams.learning-twins.export');
    Route::get('/exams/{exam}/predictions/export', [ExportController::class, 'exportPredictionReport'])->name('exams.predictions.export');
    Route::post('/exams/{exam}/fairness/analyze', [ExamFairnessController::class, 'analyze'])->name('exams.fairness.analyze');
    Route::post('/exams/{exam}/fairness/reanalyze', [ExamFairnessController::class, 'reanalyze'])->name('exams.fairness.reanalyze');
    Route::get('/exams/{exam}', [LecturerExamController::class, 'show'])->name('exams.show');
    Route::get('/exams/{exam}/edit', [LecturerExamController::class, 'edit'])->name('exams.edit');
    Route::put('/exams/{exam}', [LecturerExamController::class, 'update'])->name('exams.update');
    Route::delete('/exams/{exam}', [LecturerExamController::class, 'destroy'])->name('exams.destroy');
    Route::post('/exams/{exam}/publish', [LecturerExamController::class, 'publish'])->name('exams.publish');
    Route::get('/attempts/{attempt}/grade', [LecturerGradeController::class, 'showAttempt'])->name('grades.attempt');
    Route::post('/attempts/{attempt}/grade', [LecturerGradeController::class, 'storeGrade'])->name('grades.store');
    Route::post('/exams/{exam}/attempts/{attempt}/revision-plan/generate', [LecturerRevisionPlanController::class, 'generateForAttempt'])->name('exams.attempts.revision-plan.generate');
    Route::post('/exams/{exam}/attempts/{attempt}/revision-plan/regenerate', [LecturerRevisionPlanController::class, 'regenerateForAttempt'])->name('exams.attempts.revision-plan.regenerate');
    Route::get('/exams/{exam}/attempts/{attempt}/revision-plan', [LecturerRevisionPlanController::class, 'viewForAttempt'])->name('exams.attempts.revision-plan.show');
    Route::get('/questions/{question}/edit', [LecturerQuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [LecturerQuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [LecturerQuestionController::class, 'destroy'])->name('questions.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
