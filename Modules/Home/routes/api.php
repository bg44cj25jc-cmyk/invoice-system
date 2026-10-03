<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('home')
    ->name('home.')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::post('/request-doctor', 'HomeController@requestDoctor')->name('requestDoctor');
        Route::get('/get-speciality', 'HomeController@getSpeciality')->name('getSpeciality');
		Route::get('/get-city', 'HomeController@getCity')->name('getCity');
		Route::post('/municipalityList', 'HomeController@municipalityList')->name('municipalityList');

        Route::post('/search-doctor-app', 'HomeController@searchDoctorApp')->name('searchDoctorApp');


        Route::get('/get-faqs', 'HomeController@getFaqs')->name('getFaqs');
        Route::get('/get-languages', 'HomeController@getLanguages')->name('getLanguages');
        Route::get('/get-days', 'HomeController@getDays')->name('getDays');
        Route::get('/get-consultation', 'HomeController@getConsultation')->name('getConsultation');
        Route::post('/fetch-doctor', 'HomeController@fetchDoctor')->name('fetchDoctor');
        Route::post('/doctor-details', 'HomeController@doctorDetails')->name('doctorDetails');
        Route::post('/search-doctor', 'HomeController@searchDoctor')->name('searchDoctor');
        Route::post('/search-availableday', 'HomeController@getDoctorAvailableday')->name('getDoctorAvailableday');
        Route::get('/get-cancel-reason', 'HomeController@getCancelReason')->name('getCancelReason');
        Route::get('/get-absence-reason', 'HomeController@getAbsenceReason')->name('getAbsenceReason');
        Route::get('/get-delay-reason', 'HomeController@getDelayReason')->name('getDelayReason');
        Route::get('/get-reschedule-reason', 'HomeController@getRescheduleReason')->name('getRescheduleReason');
        Route::get('/download-app', 'HomeController@downloadApp')->name('downloadApp');

		Route::post('/get-doctor-template', 'HomeController@getDoctorTemplate')->name('getDoctorTemplate');
		Route::post('/checkadmintoken', 'HomeController@checkadmintoken')->name('checkadmintoken');
		Route::post('/add-doctor-template', 'HomeController@AddDoctorTemplate')->name('AddDoctorTemplate');
		Route::post('/edit-doctor-template', 'HomeController@EditDoctorTemplate')->name('EditDoctorTemplate');

        Route::post('/get-city-state', 'HomeController@getCityState')->name('getCityState');

        Route::post('/cms', 'HomeController@cms')->name('cms');

        Route::post('/help-center', 'HomeController@helpCenter')->name('helpCenter');

        Route::post('/get-other-doctor-template', 'HomeController@getOtherDoctorTemplate')->name('getOtherDoctorTemplate');
    });

