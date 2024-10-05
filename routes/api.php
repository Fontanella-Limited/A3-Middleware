<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resource\UserResource as User;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\ApiCallLogController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ApiSettingsController;
use App\Http\Controllers\PerformanceMonitoringController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// ---------------------------- USER MGMT. ------------------------------//
Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'index')->name('users.index');
    Route::post('/users/store', 'store')->name('users.store');
    Route::get('/users/edit/{id}', 'edit')->name('users.edit');
    Route::post('/users/update/{id}', 'update')->name('users.update');
    Route::delete('/users/delete/{id}', 'destroy')->name('users.delete');
    Route::post('/users/status/{id}', 'status')->name('users.status');
    Route::get('/users/filter', 'filter')->name('users.filter');
    Route::get('/users/{id}', 'show')->name('users.show');
});

// ---------------------------- API MGMT. ------------------------------//
Route::controller(EndpointController::class)->group(function () {
    Route::get('/endpoints', 'index')->name('endpoints.index');
    Route::post('/endpoints/store', 'store')->name('endpoints.store');
    Route::get('/endpoints/edit/{id}', 'edit')->name('endpoints.edit');
    Route::post('/endpoints/update/{id}', 'update')->name('endpoints.update');
    Route::delete('/endpoints/delete/{id}', 'destroy')->name('endpoints.delete');
    Route::post('/endpoints/status/{id}', 'status')->name('endpoints.status');
    Route::get('/endpoints/search', 'search')->name('endpoints.search');
    Route::get('/endpoints/filter', 'filter')->name('endpoints.filter');
    Route::get('/endpoints/analytics', 'analytics')->name('endpoints.analytics');
    Route::get('/endpoints/history', 'history')->name('endpoints.history');
    Route::get('/endpoints/{id}', 'show')->name('endpoints.show');
});

// ---------------------------- CALL LOG MGMT. ------------------------------//
Route::controller(ApiCallLogController::class)->group(function () {
    Route::get('/call-logs', 'index')->name('call-logs.index');
    Route::post('/call-logs/store', 'store')->name('call-logs.store');
    Route::get('/call-logs/edit/{id}', 'edit')->name('call-logs.edit');
    Route::post('/call-logs/update/{id}', 'update')->name('call-logs.update');
    Route::delete('/call-logs/delete/{id}', 'destroy')->name('call-logs.delete');
    Route::get('/call-logs/search', 'search')->name('call-logs.search');
    Route::get('/call-logs/filter', 'filter')->name('call-logs.filter');
    Route::get('/call-logs/analytics', 'analytics')->name('call-logs.analytics');
    Route::get('/call-logs/{id}', 'show')->name('call-logs.show');
});

// ---------------------------- API KEY MGMT. ------------------------------//
Route::controller(ApiKeyController::class)->group(function () {
    Route::get('/apikeys', 'index')->name('apikeys.index');
    Route::post('/apikeys/store', 'store')->name('apikeys.store');
    Route::get('/apikeys/edit/{id}', 'edit')->name('apikeys.edit');
    Route::post('/apikeys/update/{id}', 'update')->name('apikeys.update');
    Route::delete('/apikeys/delete/{id}', 'destroy')->name('apikeys.delete');
    Route::post('/apikeys/regenerate/{id}', 'regenerate')->name('apikeys.regenerate');
    Route::post('/apikeys/revoke/{id}', 'revoke')->name('apikeys.revoke');
    Route::get('/apikeys/analytics', 'analytics')->name('apikeys.analytics');
    Route::get('/apikeys/search', 'search')->name('apikeys.search');
    Route::get('/apikeys/filter', 'filter')->name('apikeys.filter');
    Route::get('/apikeys/{id}', 'show')->name('apikeys.show');
});

// ---------------------------- API SETTINGS ------------------------------//
Route::controller(ApiSettingsController::class)->group(function () {
    Route::get('/settings', 'index')->name('settings.index');
    Route::post('/settings/store', 'store')->name('settings.store');
    Route::get('/settings/edit/{id}', 'edit')->name('settings.edit');
    Route::post('/settings/update/{id}', 'update')->name('settings.update');
    Route::delete('/settings/delete/{id}', 'destroy')->name('settings.delete');
    Route::get('/settings/{id}', 'show')->name('settings.show');
});

// ---------------------------- PERFORMANCE MONITORING ------------------------------//
Route::controller(PerformanceMonitoringController::class)->group(function () {
    Route::get('/performances', 'index')->name('performances.index');
    Route::get('/performances/logs', 'logs')->name('performances.logs');
    Route::get('/performances/filter', 'filter')->name('performances.filter');
});
