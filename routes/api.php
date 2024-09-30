<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resource\UserResource as User;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApiCallLogController;
use App\Http\Controllers\ApiKeyController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// ----------------------------USER MGMT. ------------------------------//
Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'index')->name('users.index');
    Route::post('/users/store', 'store')->name('users.store');
    Route::get('/users/edit/{id}', 'edit')->name('users.edit');
    Route::post('/users/update/{id}', 'update')->name('users.update');
    Route::delete('/users/delete/{id}', 'destroy')->name('users.delete');
    Route::get('/users/filter', 'filter')->name('users.filter');
    Route::get('/users/{id}', 'show')->name('users.show');
});

// ----------------------------API MGMT. ------------------------------//
Route::controller(ApiController::class)->group(function () {
    Route::get('/apis', 'index')->name('apis.index');
    Route::post('/apis/store', 'store')->name('apis.store');
    Route::get('/apis/edit/{id}', 'edit')->name('apis.edit');
    Route::post('/apis/update/{id}', 'update')->name('apis.update');
    Route::delete('/apis/delete/{id}', 'destroy')->name('apis.delete');
    Route::post('/apis/status', 'status')->name('apis.status');
    Route::get('/apis/filter', 'filter')->name('apis.filter');
    Route::get('/apis/analytics', 'analytics')->name('apis.analytics');
    Route::get('/apis/history', 'history')->name('apis.history');
    Route::get('/apis/{id}', 'show')->name('apis.show');
});

// ----------------------------CALL LOG MGMT. ------------------------------//
Route::controller(ApiCallLogController::class)->group(function () {
    Route::get('/call-logs', 'index')->name('call-logs.index');
    Route::post('/call-logs/store', 'store')->name('call-logs.store');
    Route::get('/call-logs/edit/{id}', 'edit')->name('call-logs.edit');
    Route::post('/call-logs/update/{id}', 'update')->name('call-logs.update');
    Route::delete('/call-logs/delete/{id}', 'destroy')->name('call-logs.delete');
    Route::get('/call-logs/filter', 'filter')->name('call-logs.filter');
    Route::get('/call-logs/analytics', 'analytics')->name('call-logs.analytics');
    Route::get('/call-logs/{id}', 'show')->name('call-logs.show');
});

// ----------------------------API KEY MGMT. ------------------------------//
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
