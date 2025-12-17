<?php

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

    Route::get('/prospect-tags', [ProspectTagController::class, 'index']);
    Route::post('/prospect-tags', [ProspectTagController::class, 'store']);
    Route::get('/prospect-tags/{prospectTag}', [ProspectTagController::class, 'show']);
    Route::put('/prospect-tags/{prospectTag}', [ProspectTagController::class, 'update']);
    Route::delete('/prospect-tags/{prospectTag}', [ProspectTagController::class, 'destroy']);
    
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
    Route::post('/tasks/{task}/tags', [TaskController::class, 'updateTags']);

    Route::post('/tasks/docs', [TaskDocController::class, 'store']);
    Route::delete('/tasks/docs/{id}', [TaskDocController::class, 'destroy']);

    Route::get('/prospect-folders', [ProspectFolderController::class, 'index']);
    Route::post('/prospect-folders', [ProspectFolderController::class, 'store']);
    Route::get('/prospect-folders/{prospectFolder}', [ProspectFolderController::class, 'show']);
    Route::put('/prospect-folders/{prospectFolder}', [ProspectFolderController::class, 'update']);
    Route::delete('/prospect-folders/{prospectFolder}', [ProspectFolderController::class, 'destroy']);

    Route::get('/prospect-contacts/{clientId}', [ProspectContactController::class, 'index']);
    Route::post('/prospect-contacts', [ProspectContactController::class, 'store']);
    Route::get('/prospect-contacts/show/{id}', [ProspectContactController::class, 'show']);
    Route::put('/prospect-contacts/{id}', [ProspectContactController::class, 'update']);
    Route::delete('/prospect-contacts/{id}', [ProspectContactController::class, 'destroy']);

    Route::post('/org-docs', [OrgDocController::class, 'store']);
    Route::delete('/org-docs/{id}', [OrgDocController::class, 'destroy']);
});