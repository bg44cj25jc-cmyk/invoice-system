<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'throttle:60,1'])
    ->prefix('setting')
    ->name('setting.')
    ->group(function () {
        Route::get('/fetch-setting', 'SettingController@fetchSetting')->name('fetchSetting');
        Route::post('/update-setting', 'SettingController@updateSetting')->name('updateSetting');
        Route::get('/fetch-question', 'SettingController@fetchQuestion')->name('fetchQuestion');
        Route::post('/update-question', 'SettingController@updateQuestion')->name('updateQuestion');
        Route::post('/visit-question', 'SettingController@visitQuestion')->name('visitQuestion');
        Route::post('/delete-question', 'SettingController@deleteQuestion')->name('deleteQuestion');
        Route::post('/delete-option', 'SettingController@deleteOption')->name('deleteOption');
    });
