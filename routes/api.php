<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SprintController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
        Route::post('/organizations/{organization}/members', [OrganizationController::class, 'addMember']);
        Route::delete('/organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);

        Route::get('/organizations/{organization}/projects', [ProjectController::class, 'index']);
        Route::post('/organizations/{organization}/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{project}/members', [ProjectController::class, 'addMember']);
        Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);

        Route::get('/projects/{project}/board', [ColumnController::class, 'board']);
        Route::post('/projects/{project}/columns', [ColumnController::class, 'store']);
        Route::put('/columns/{column}', [ColumnController::class, 'update']);
        Route::delete('/columns/{column}', [ColumnController::class, 'destroy']);
        Route::post('/projects/{project}/columns/reorder', [ColumnController::class, 'reorder']);

        Route::get('/projects/{project}/issues', [IssueController::class, 'index']);
        Route::post('/projects/{project}/issues', [IssueController::class, 'store']);
        Route::get('/issues/{issue}', [IssueController::class, 'show']);
        Route::put('/issues/{issue}', [IssueController::class, 'update']);
        Route::delete('/issues/{issue}', [IssueController::class, 'destroy']);
        Route::post('/issues/{issue}/move', [IssueController::class, 'move']);
        Route::post('/issues/{issue}/labels', [IssueController::class, 'syncLabels']);

        Route::get('/issues/{issue}/comments', [CommentController::class, 'index']);
        Route::post('/issues/{issue}/comments', [CommentController::class, 'store']);
        Route::put('/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        Route::get('/issues/{issue}/attachments', [IssueController::class, 'attachments']);
        Route::post('/issues/{issue}/attachments', [IssueController::class, 'uploadAttachment']);
        Route::delete('/attachments/{attachment}', [IssueController::class, 'deleteAttachment']);

        Route::get('/projects/{project}/sprints', [SprintController::class, 'index']);
        Route::post('/projects/{project}/sprints', [SprintController::class, 'store']);
        Route::put('/sprints/{sprint}', [SprintController::class, 'update']);
        Route::post('/sprints/{sprint}/start', [SprintController::class, 'start']);
        Route::post('/sprints/{sprint}/complete', [SprintController::class, 'complete']);
        Route::post('/sprints/{sprint}/issues', [SprintController::class, 'assignIssues']);
        Route::delete('/sprints/{sprint}', [SprintController::class, 'destroy']);

        Route::get('/projects/{project}/labels', [LabelController::class, 'index']);
        Route::post('/projects/{project}/labels', [LabelController::class, 'store']);
        Route::put('/labels/{label}', [LabelController::class, 'update']);
        Route::delete('/labels/{label}', [LabelController::class, 'destroy']);

        Route::get('/search', [SearchController::class, 'index']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/projects/{project}/stats', [DashboardController::class, 'projectStats']);
    });
});