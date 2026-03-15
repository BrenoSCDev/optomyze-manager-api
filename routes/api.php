<?php

use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ClientPortalAdminController;
use App\Http\Controllers\ClientPortalAuthController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\CrmCompanyController;
use App\Http\Controllers\CrmUserController;
use App\Http\Controllers\DriveFileController;
use App\Http\Controllers\DriveFolderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientContractController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\ClientTagController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\OrgDocController;
use App\Http\Controllers\ProspectContactController;
use App\Http\Controllers\ProspectFolderController;
use App\Http\Controllers\ProspectTagController;
use App\Http\Controllers\TaskCategoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDocController;
use App\Http\Controllers\TaskTagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// ──────────────────────────────────────────
// Client Portal — Authentication (public, rate-limited)
// ──────────────────────────────────────────
Route::post('/portal/{slug}/auth', [ClientPortalAuthController::class, 'authenticate'])
    ->middleware('throttle:5,1');

// ──────────────────────────────────────────
// Client Portal — Data (portal token required)
// ──────────────────────────────────────────
Route::prefix('portal')->middleware('portal.access')->group(function () {
    Route::get('/me',                                [ClientPortalController::class, 'me']);
    Route::get('/tasks',                             [ClientPortalController::class, 'tasks']);
    Route::get('/payments',                          [ClientPortalController::class, 'payments']);
    Route::get('/drive',                             [ClientPortalController::class, 'drive']);
    Route::get('/drive/folders/{id}',                [ClientPortalController::class, 'folder']);
    Route::get('/drive/files/{id}/preview',          [ClientPortalController::class, 'previewFile']);
    Route::get('/drive/files/{id}/download',         [ClientPortalController::class, 'downloadFile']);
    Route::get('/contracts',                         [ClientPortalController::class, 'contracts']);
    Route::get('/contracts/{id}/download',           [ClientPortalController::class, 'downloadContract']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
    
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
    Route::post('/clients/{id}/tags/{type}', [ClientController::class, 'updateTags']);
    Route::post('/clients/{client}/convert', [ClientController::class, 'convertProspect']);

    Route::get('/clients/prospects', [ClientController::class, 'prospects']);
    Route::post('/clients/prospects', [ClientController::class, 'storeProspects']);

    Route::post('/prospects/import', [ClientController::class, 'importProspects']);

    Route::post('/client-contracts', [ClientContractController::class, 'store']);
    Route::delete('/client-contracts/{id}', [ClientContractController::class, 'destroy']);

    Route::get('/clients/{clientId}/payments', [ClientPaymentController::class, 'index']);
    Route::post('/client-payments', [ClientPaymentController::class, 'store']);
    Route::put('/client-payments/{id}', [ClientPaymentController::class, 'update']);
    Route::delete('/client-payments/{id}', [ClientPaymentController::class, 'destroy']);

    Route::get('/task-categories', [TaskCategoryController::class, 'index']);
    Route::post('/task-categories', [TaskCategoryController::class, 'store']);
    Route::get('/task-categories/{taskCategory}', [TaskCategoryController::class, 'show']);
    Route::put('/task-categories/{taskCategory}', [TaskCategoryController::class, 'update']);
    Route::delete('/task-categories/{taskCategory}', [TaskCategoryController::class, 'destroy']);

    Route::get('/client-tags', [ClientTagController::class, 'index']);
    Route::post('/client-tags', [ClientTagController::class, 'store']);
    Route::get('/client-tags/{clientTag}', [ClientTagController::class, 'show']);
    Route::put('/client-tags/{clientTag}', [ClientTagController::class, 'update']);
    Route::delete('/client-tags/{clientTag}', [ClientTagController::class, 'destroy']);
    
    Route::get('/task-tags', [TaskTagController::class, 'index']);
    Route::post('/task-tags', [TaskTagController::class, 'store']);
    Route::get('/task-tags/{taskTag}', [TaskTagController::class, 'show']);
    Route::put('/task-tags/{taskTag}', [TaskTagController::class, 'update']);
    Route::delete('/task-tags/{taskTag}', [TaskTagController::class, 'destroy']);

    Route::get('/settings', [DataController::class, 'getSettings']);
    Route::get('/dashboard', [DataController::class, 'dashboard']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/grouped-by-category', [TaskController::class, 'tasksGroupedByCategory']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/task-categories/{category}/tasks', [TaskController::class, 'tasksByCategory']);
    Route::patch('/tasks/{task}/move', [TaskController::class, 'moveCategory']);
    Route::post('/tasks/{task}/tags', [TaskController::class, 'updateTags']);

    Route::post('/tasks/docs', [TaskDocController::class, 'store']);
    Route::delete('/tasks/docs/{id}', [TaskDocController::class, 'destroy']);

    Route::post('/org-docs', [OrgDocController::class, 'store']);
    Route::delete('/org-docs/{id}', [OrgDocController::class, 'destroy']);

    // ──────────────────────────────────────────
    // Client Portal — Admin Management
    // ──────────────────────────────────────────
    Route::prefix('clients/{client}/portal')->group(function () {
        Route::get('/',               [ClientPortalAdminController::class, 'status']);
        Route::post('/enable',        [ClientPortalAdminController::class, 'enable']);
        Route::post('/disable',       [ClientPortalAdminController::class, 'disable']);
        Route::post('/regenerate-key',[ClientPortalAdminController::class, 'regenerateKey']);
        Route::patch('/slug',         [ClientPortalAdminController::class, 'updateSlug']);
    });

    // CRM Companies (proxied to external CRM API)
    Route::get('/crm/companies', [CrmCompanyController::class, 'index']);
    Route::post('/crm/companies', [CrmCompanyController::class, 'store']);
    Route::get('/crm/companies/{id}', [CrmCompanyController::class, 'show']);
    Route::put('/crm/companies/{id}', [CrmCompanyController::class, 'update']);
    Route::delete('/crm/companies/{id}', [CrmCompanyController::class, 'destroy']);
    Route::post('/crm/companies/{id}/restore', [CrmCompanyController::class, 'restore']);

    // CRM Users (proxied to external CRM API)
    Route::get('/crm/users', [CrmUserController::class, 'index']);
    Route::post('/crm/users', [CrmUserController::class, 'store']);
    Route::get('/crm/users/{id}', [CrmUserController::class, 'show']);
    Route::put('/crm/users/{id}', [CrmUserController::class, 'update']);
    Route::patch('/crm/users/{id}/deactivate', [CrmUserController::class, 'deactivate']);
    Route::delete('/crm/users/{id}', [CrmUserController::class, 'destroy']);

    // ──────────────────────────────────────────
    // Calendar
    // ──────────────────────────────────────────

    Route::get('/calendar/events',                        [CalendarEventController::class, 'index']);
    Route::post('/calendar/events',                       [CalendarEventController::class, 'store']);
    Route::get('/calendar/events/{calendarEvent}',        [CalendarEventController::class, 'show']);
    Route::put('/calendar/events/{calendarEvent}',        [CalendarEventController::class, 'update']);
    Route::delete('/calendar/events/{calendarEvent}',     [CalendarEventController::class, 'destroy']);

    // ──────────────────────────────────────────
    // Drive — File Management
    // ──────────────────────────────────────────

    // Folders
    Route::get('/drive/folders', [DriveFolderController::class, 'index']);
    Route::post('/drive/folders', [DriveFolderController::class, 'store']);
    Route::get('/drive/folders/{folder}', [DriveFolderController::class, 'show']);
    Route::put('/drive/folders/{folder}', [DriveFolderController::class, 'update']);
    Route::delete('/drive/folders/{folder}', [DriveFolderController::class, 'destroy']);
    Route::patch('/drive/folders/{folder}/move', [DriveFolderController::class, 'move']);

    // Files — bulk routes must be declared before /{file} to avoid route-model binding conflicts
    Route::post('/drive/files/bulk', [DriveFileController::class, 'bulkStore']);
    Route::delete('/drive/files/bulk', [DriveFileController::class, 'bulkDestroy']);
    Route::post('/drive/files/bulk/download', [DriveFileController::class, 'bulkDownload']);

    Route::post('/drive/files', [DriveFileController::class, 'store']);
    Route::get('/drive/files/{file}', [DriveFileController::class, 'show']);
    Route::delete('/drive/files/{file}', [DriveFileController::class, 'destroy']);
    Route::patch('/drive/files/{file}/move', [DriveFileController::class, 'move']);
    Route::get('/drive/files/{file}/url', [DriveFileController::class, 'temporaryUrl']);
    Route::get('/drive/files/{file}/preview', [DriveFileController::class, 'preview']);

    // Download — named route required for signed URL generation
    Route::get('/drive/files/{file}/download', [DriveFileController::class, 'download'])
        ->name('drive.files.download');
});