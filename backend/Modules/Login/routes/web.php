<?php

use Illuminate\Support\Facades\Route;
use Modules\Login\App\Http\Controllers\LoginController;

Route::prefix('login')->group(function() {
    Route::get('/', 'LoginController@index')->name('login');
    Route::post('/', 'LoginController@submitLogin')->name('submitLogin');
    Route::get('/logout', 'LoginController@logOut')->name('logOut');
});

Route::middleware(['auth'])->group(function () {
    // Change Password Routes
    Route::get('/change-password', [LoginController::class, 'showChangePasswordForm'])
        ->name('change-password');
    
    Route::post('/change-password', [LoginController::class, 'updatePassword'])
        ->name('password.update');
    
    Route::post('/validate-password', [LoginController::class, 'validatePassword'])
        ->name('password.validate');
});
