<?php

use App\Http\Controllers\FirestoreApiController;
use Illuminate\Support\Facades\Route;

// Render dynamic HRD views (fully modular Laravel Blade!)
Route::get('/', function() {
    return view('hrd');
});
Route::get('/index.php', function() {
    return view('hrd');
});
Route::get('/public/app.html', function() {
    return view('hrd');
});

// Firestore API routes v1
Route::group(['prefix' => 'api/v1'], function () {
    Route::get('/health', [FirestoreApiController::class, 'health']);
    Route::post('/anon', [FirestoreApiController::class, 'anon']);
    Route::post('/auth/hrd', [FirestoreApiController::class, 'authHrd']);
    Route::post('/auth/pelamar', [FirestoreApiController::class, 'authPelamar']);
    Route::post('/batch', [FirestoreApiController::class, 'batch']);
    Route::get('/changes', [FirestoreApiController::class, 'changes']);

    // Dynamic document endpoints (matches paths with multiple segments like applicants/123/messages/abc)
    Route::any('/doc/{path}', [FirestoreApiController::class, 'handleDoc'])
        ->where('path', '.*');

    // Dynamic collection endpoints (matches paths with multiple segments like applicants/123/messages)
    Route::any('/collection/{path}', [FirestoreApiController::class, 'handleCollection'])
        ->where('path', '.*');
});
