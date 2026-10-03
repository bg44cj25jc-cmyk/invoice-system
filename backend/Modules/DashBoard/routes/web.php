<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\DashBoard\App\Http\Controllers\DashBoardController;

Route::middleware('auth')
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::get('/', 'DashBoardController@index');
        Route::get('/doctor-request', 'DashBoardController@doctorRequest');
		Route::get('/received-requests', 'DashBoardController@receivedRequests');
		Route::post('/update-status', 'DashBoardController@updateStatus');
        Route::post('/update-user-status', 'DashBoardController@updateUserStatus');
		Route::post('/update-comment', 'DashBoardController@updateComment');


        Route::get('/language', 'DashBoardController@language');
        Route::get('/create-language', 'DashBoardController@createLanguage');
        Route::post('/create-language', 'DashBoardController@submitLanguage');
        Route::get('/edit-language/{id}', 'DashBoardController@editLanguage');
        Route::post('/edit-language/{id}', 'DashBoardController@updateLanguage');
		
        Route::get('/speciality', 'DashBoardController@speciality');
        Route::get('/create-speciality', 'DashBoardController@createSpeciality');
        Route::post('/create-speciality', 'DashBoardController@submitSpeciality');
        Route::get('/edit-speciality/{id}', 'DashBoardController@editSpeciality');
        Route::post('/edit-speciality/{id}', 'DashBoardController@updateSpeciality');
        Route::get('/faq', 'DashBoardController@faq');
        Route::get('/create-faq', 'DashBoardController@createFaq');
        Route::post('/create-faq', 'DashBoardController@submitFaq');
        Route::get('/edit-faq/{id}', 'DashBoardController@editFaq');
        Route::post('/edit-faq/{id}', 'DashBoardController@updateFaq');
        Route::get('/log-out', 'DashBoardController@logOut');
        Route::post('/delete-request', 'DashBoardController@deleteRequest');
        Route::post('/approve-request', 'DashBoardController@approveRequest');
        Route::get('/standard-message', 'DashBoardController@welcome');
        Route::post('/standard-message', 'DashBoardController@updateWelcome');
        Route::get('/cms', 'DashBoardController@cms');

        Route::get('/help-center', 'DashBoardController@helpCenter');

        Route::get('/edit-cms/{id}', 'DashBoardController@editCms');
        Route::post('/edit-cms/{id}', 'DashBoardController@updateCms');
        Route::post('/delete-help', 'DashBoardController@deleteHelp');

        //medicine section start
        Route::post('/bulk-delete', 'DashBoardController@bulkDelete');
        Route::post('/upload-csv', 'DashBoardController@uploadCSV');
        Route::get('/medicine', 'DashBoardController@medicine');
        Route::get('/delete-medicine/{id}', 'DashBoardController@deleteMedicine');
        //Route::post('/delete-all-medicines','DashBoardController@deleteAllMedicines');
        Route::match(['post', 'delete'], '/delete-all-medicines', 'DashBoardController@deleteAllMedicines')->name('dashboard.delete.all.medicines');
        Route::get('/medicine', 'DashBoardController@medicine');
        Route::get('/create-medicine', 'DashBoardController@createMedicine');
        Route::post('/create-medicine', 'DashBoardController@submitMedicine');
        Route::get('/edit-medicine/{id}', 'DashBoardController@editMedicine');
        Route::post('/edit-medicine/{id}', 'DashBoardController@updateMedicine');
        //medicine section end
    });
