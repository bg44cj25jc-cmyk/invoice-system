<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\User\App\Http\Controllers\UserController;

Route::middleware('auth')
    ->prefix('user')
    ->name('user.')
    ->group(function () {
		
	
		
		
		
		Route::get('/company-list', [UserController::class, 'companyListPage']);
		Route::get('/companies', [UserController::class, 'companyList']); // JSON API
		Route::get('/companies/{id}', [UserController::class, 'companyDetails']);
		Route::get('/company-details/{id}', [UserController::class, 'companyDetailsPage']);
		Route::post('/company/status-update', [UserController::class, 'updateCompanyStatus']);
		
		Route::get('/employee-list', [UserController::class, 'employeeList'])->name('employeeList');
		Route::get('/employees', [UserController::class, 'employeeListJson']);

        Route::get('/view-employee/{id}', [UserController::class, 'viewEmployee'])->name('viewEmployee');

        Route::get('/payment-history', [UserController::class, 'paymentList'])->name('paymentList');
        Route::get('/payment-details/{id}', [UserController::class,'paymentDetails'])->name('paymentDetails');


        Route::get('/invoice-history', [UserController::class, 'invoiceList'])->name('invoiceList');
        Route::get('/invoice-details/{id}', [UserController::class, 'invoiceDetails'])->name('invoiceDetails');
  
		

		
		
		
		
		
		
		
		
		
		
		
		
		
		
//		OLD Code 
		
        Route::get('/create', 'UserController@create');
        Route::post('/create', 'UserController@createUser');
        Route::get('/user-list-admin', 'UserController@userListAdmin');

//		Route::get('/edit/{id}', 'UserController@edit');
//		Route::post('/edit{id}', 'UserController@updateuser');
		Route::get('/edit/{id}', [UserController::class, 'edit'])->name('edit');
        Route::post('/edit/{id}', [UserController::class, 'updateUser'])->name('update');


		//front user section
		Route::get('/edit-user-by-admin/{id}', [UserController::class, 'edit_user_by_admin'])->name('edit');
        Route::post('/edit-user-by-admin/{id}', [UserController::class, 'updateUserByAdmin'])->name('update');


		Route::get('/user-list-create-by-admin', 'UserController@userListCreateByAdmin');
		Route::get('/create-front-user-view', 'UserController@createFrontUserView');
        Route::post('/createFrontUser', 'UserController@createFrontUser');

		Route::get('/get-user-list-created-by-admin', 'UserController@getUserListCreatedByAdmin');
		Route::get('/get-user-list-created-by-admin2', 'UserController@getUserListCreatedByAdmin2');
		Route::post('/resend-user-admin', 'UserController@resendUserAdmin');
		//front user section end


        Route::get('/get-archive-list', 'UserController@getArchiveList');

        Route::post('/delete-user-admin', 'UserController@deleteUserAdmin');

        Route::post('/delete-user-main', 'UserController@deleteUserMain');


		Route::get('/get-appoint-list-byuser/{id}', 'UserController@getAppointListByuser');
		Route::get('/userAppointList', 'UserController@userAppointList');

		Route::get('/patientAppointDetails', 'UserController@patientAppointDetails');
		Route::get('/patient-list', 'UserController@patientListView');

		Route::get('/template-list-admin', 'UserController@templateListAdmin');
		Route::get('/get-all-template-list', 'UserController@getAllTemplateList');
		Route::post('/delete-template-admin', 'UserController@deleteTemplateAdmin');
		Route::post('/go-template-admin', 'UserController@goTemplateAdmin');
		//Route::get('/template-list-admin', 'UserController@templateListAdmin');
		Route::get('/get-municipalities/{cityId}', [UserController::class, 'getMunicipalities']);

        Route::post('/delete-patient', 'UserController@deletePatient');

    });
