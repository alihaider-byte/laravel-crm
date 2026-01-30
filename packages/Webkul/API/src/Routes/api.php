<?php

use Illuminate\Support\Facades\Route;
use Webkul\API\Http\Controllers\V1\AuthController;
use Webkul\API\Http\Controllers\V1\LeadController;
use Webkul\API\Http\Controllers\V1\OrganizationController;
use Webkul\API\Http\Controllers\V1\PersonController;
use Webkul\API\Http\Controllers\V1\LookupController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the CRM.
| These routes are loaded by the APIServiceProvider.
|
*/

// Public Auth Routes
Route::prefix('api/v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::prefix('api/v1')->middleware(['auto.service.auth', 'auth:sanctum'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Person/Contact Routes
    |--------------------------------------------------------------------------
    */
    Route::controller(PersonController::class)->prefix('persons')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/search', 'search');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::get('/{id}/activities', 'activities');
        Route::post('/{id}/tags', 'attachTags');
        Route::delete('/{id}/tags', 'detachTags');
    });

    /*
    |--------------------------------------------------------------------------
    | Organization Routes
    |--------------------------------------------------------------------------
    */
    Route::controller(OrganizationController::class)->prefix('organizations')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Lead Routes
    |--------------------------------------------------------------------------
    */
    Route::controller(LeadController::class)->prefix('leads')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/search', 'search');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::put('/{id}/stage', 'updateStage');
        Route::get('/{id}/activities', 'activities');
        Route::post('/{id}/activities', 'storeActivity');
        Route::post('/{id}/tags', 'attachTags');
        Route::delete('/{id}/tags', 'detachTags');
    });

    /*
    |--------------------------------------------------------------------------
    | Lookup/Reference Routes
    |--------------------------------------------------------------------------
    */
    Route::controller(LookupController::class)->prefix('lookups')->group(function () {
        Route::get('/sources', 'sources');
        Route::get('/types', 'types');
        Route::get('/pipelines', 'pipelines');
        Route::get('/stages/{pipeline_id?}', 'stages');
        Route::get('/tags', 'tags');
        Route::post('/tags', 'storeTag');
        Route::get('/users', 'users');
    });
});
