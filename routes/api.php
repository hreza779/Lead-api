<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentQuestionController;
use App\Http\Controllers\AssessmentStepController;
use App\Http\Controllers\AssessmentTemplateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamManagerController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ExamSetController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SupportMessageController;
use App\Http\Controllers\SupportTicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Exam System
    Route::apiResource('exams', ExamController::class);
    Route::post('exams/{exam}/questions', [ExamController::class, 'attachQuestions']);
    Route::delete('exams/{exam}/questions/{question}', [ExamController::class, 'detachQuestion']);

    Route::apiResource('questions', QuestionController::class);

    // Exam Assignments
    Route::apiResource('exam-assignments', ExamManagerController::class);
    Route::post('exam-assignments/{assignment}/start', [ExamManagerController::class, 'start']);
    Route::post('exam-assignments/{assignment}/complete', [ExamManagerController::class, 'complete']);

    // Exam Sets
    Route::apiResource('exam-sets', ExamSetController::class);
    Route::post('exam-sets/{examSet}/exams', [ExamSetController::class, 'addExams']);

    // Exam Results
    Route::apiResource('exam-results', ExamResultController::class);
    Route::post('exam-results/{result}/submit', [ExamResultController::class, 'submit']);
    Route::get('exam-results/{result}/report', [ExamResultController::class, 'report']);

    // Assessment System
    Route::apiResource('assessment-templates', AssessmentTemplateController::class);
    Route::apiResource('assessment-steps', AssessmentStepController::class);
    Route::apiResource('assessment-questions', AssessmentQuestionController::class);
    Route::apiResource('assessments', AssessmentController::class);

    // Custom Assessment Routes
    Route::put('assessment-steps/{step}/reorder', [AssessmentStepController::class, 'reorder']);
    Route::put('assessment-questions/{question}/reorder', [AssessmentQuestionController::class, 'reorder']);
    Route::post('assessments/{assessment}/submit', [AssessmentController::class, 'submit']);

    // Company & Manager Management
    Route::get('companies/my-companies', [CompanyController::class, 'myCompanies']);
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('managers', ManagerController::class);

    // Support System
    Route::apiResource('support-tickets', SupportTicketController::class);
    Route::get('support-tickets/{ticket}/messages', [SupportMessageController::class, 'index']);
    Route::post('support-tickets/{ticket}/messages', [SupportMessageController::class, 'store']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
});



