<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\User\app\Http\Controllers\SectionController;
 use Modules\User\app\Http\Controllers\ProductController;
use Modules\User\app\Http\Controllers\CategoryController;
use Modules\User\app\Http\Controllers\InvoiceController;
use Modules\User\app\Http\Controllers\LandsbankinnTestController;




// Test Routes (Remove these in production!)
Route::prefix('landsbankinn/test')->group(function () {
    Route::get('/connection', 'LandsbankinnTestController@testConnection');
    Route::post('/create-template', 'LandsbankinnTestController@testCreateTemplate');
});

// Landsbankinn OAuth Routes
Route::prefix('landsbankinn')->group(function () {
    // Initiate OAuth flow
    Route::get('/oauth/authorize', 'LandsbankinnOAuthController@authorize')
        ->name('landsbankinn.oauth.authorize');
    
    // OAuth callback (THIS IS YOUR REDIRECT URL)
    Route::get('/oauth/callback', 'LandsbankinnOAuthController@callback')
        ->name('landsbankinn.oauth.callback');
    
    // Protected routes (require authentication)
    Route::middleware('auth:api')->group(function () {
        // Check connection status
        Route::get('/oauth/status', 'LandsbankinnOAuthController@status')
            ->name('landsbankinn.oauth.status');
        
        // Refresh token
        Route::post('/oauth/refresh', 'LandsbankinnOAuthController@refresh')
            ->name('landsbankinn.oauth.refresh');
        
        // Disconnect
        Route::post('/oauth/disconnect', 'LandsbankinnOAuthController@disconnect')
            ->name('landsbankinn.oauth.disconnect');
    });
});












Route::prefix('user')
    ->name('user.')
    ->group(function () {
		
		Route::post('/paypal/webhook', 'PaypalController@handle');
		
		Route::get('/cronUpdatePlans', 'PaypalController@cronUpdatePlans')->name('cron.updatePlans');
		Route::get('/cron/reminder-email', 'PaypalController@cronReminderEmail')->name('cron.reminderEmail');

		
        Route::post('/login', 'UserController@login')->name('login');
        Route::post('/CreateAccount', 'UserController@CreateAccount')->name('CreateAccount');
		Route::post('/CreateAccount_Update', 'UserController@CreateAccount_Update')->name('CreateAccount_Update');
		Route::post('/CheckVerificationCode', 'UserController@CheckVerificationCode')->name('CheckVerificationCode');
		Route::post('/SetupPassword', 'UserController@SetupPassword')->name('SetupPassword');
		Route::post('/MoreAboutYourCompany', 'UserController@MoreAboutYourCompany')->name('MoreAboutYourCompany');
		Route::post('/getCountryList', 'UserController@getCountryList')->name('getCountryList');
		Route::post('/getPlansWithFeatures', 'UserController@getPlansWithFeatures')->name('getPlansWithFeatures');
		Route::post('/ResendOtp', 'UserController@ResendOtp')->name('ResendOtp');
		
		Route::get('/language/{lang}', 'UserController@getAllLanguage');

		
		Route::get('/employee/email/{token}', 'EmployeeController@getEmployeeByToken')->name('getEmployeeByToken');
		Route::post('/setupEmployeeAccount/{token}', 'EmployeeController@setupEmployeeAccount')->name('setupEmployeeAccount');
		Route::patch('/updateEmployeeStatusNotLogin/{id}', 'EmployeeController@updateEmployeeStatusNotLogin')->name('updateEmployeeStatusNotLogin');
		
		Route::post('/SelectPlan', 'PaypalController@SelectPlan')->name('SelectPlan');
		
        Route::post('/activate-account', 'UserController@activateAccount')->name('activateAccount');
		
		// Forgot password (send reset email)
			Route::post('/forgot-password', 'UserController@forgotPassword')->name('forgotPassword');

// Reset password using token
			Route::post('/reset-password/{token}', 'UserController@resetPassword')->name('resetPassword');

		
		Route::post('/getPostalCodesList', 'UserController@getPostalCodesList')->name('getPostalCodesList');
		
		
//		Route::post('/addEmployee', 'UserController@addEmployee')->name('addEmployee');
//		Route::get('/employees', 'UserController@getEmployees')->name('getEmployees');
		
		Route::post('/getUserDetailsBeforeLogin', 'UserController@getUserDetailsBeforeLogin')->name('getUserDetailsBeforeLogin');
		
		
		Route::get('/paypal/success', function () {
			return response()->json(['message' => 'Payment Successful']);
		})->name('paypal.success');

		Route::get('/paypal/cancel', function () {
			return response()->json(['message' => 'Payment Cancelled']);
		})->name('paypal.cancel');
        
    });
Route::middleware('auth:api')
    ->prefix('user')
    ->name('user.')
    ->group(function () {
		
		Route::get('/connection', 'LandsbankinnTestController@testConnection');
		
		
		Route::post('/dashboard/summary', 'InvoiceController@getDashboardSummary')->name('dashboard.summary');
		Route::post('/dashboard/top-products', 'InvoiceController@getTopProducts')->name('dashboard.topProducts');
		Route::post('/dashboard/sales-by-year', 'InvoiceController@getSalesByYear')->name('dashboard.salesByYear');

		
		Route::post('/invoices', 'InvoiceController@createInvoice')->name('invoices.create');
        Route::post('/getInvoicesList', 'InvoiceController@getInvoicesList')->name('getInvoicesList');
        Route::post('/getInvoice', 'InvoiceController@getInvoice')->name('invoices.show');
        Route::put('/invoices/{id}', 'InvoiceController@updateInvoice')->name('invoices.update');
        Route::post('/deleteInvoice', 'InvoiceController@deleteInvoice')->name('deleteInvoice');
        Route::post('/invoices/{id}/send', 'InvoiceController@sendInvoice')->name('invoices.send');
        Route::post('/invoices/{id}/resend-email', 'InvoiceController@resendInvoiceEmail')->name('invoices.resendInvoiceEmail');		
		Route::patch('/invoices/{id}/payment-status', 'InvoiceController@updatePaymentStatus')->name('invoices.updatePaymentStatus');
		
		// Bank Claim Routes
		Route::get('/bank-claims/templates', 'InvoiceController@getBankClaimTemplates')->name('bank.claims.templates');
		Route::get('/invoices/{id}/bank-claim-status', 'InvoiceController@getBankClaimStatus')->name('invoices.bankClaimStatus');
		Route::post('/invoices/{id}/cancel-bank-claim', 'InvoiceController@cancelBankClaim')->name('invoices.cancelBankClaim');
		Route::post('/invoices/{id}/retry-bank-claim', 'InvoiceController@retryBankClaim')->name('invoices.retryBankClaim');

        //Route::post('/userDetails', 'UserController@userDetails')->name('userDetails');
		Route::post('/getUserDetails', 'UserController@getUserDetails')->name('getUserDetails');
		Route::post('/editUserDetails', 'UserController@editUserDetails')->name('editUserDetails');
		Route::post('/editUserBankAndGlnDetails', 'UserController@editUserBankAndGlnDetails')->name('editUserBankAndGlnDetails');
		
        Route::post('/changePassword', 'UserController@changePassword')->name('changePassword');
        Route::post('/logout', 'UserController@logout')->name('logout');
		
		Route::post('/OrderHistory', 'UserController@OrderHistory')->name('OrderHistory');
		Route::delete('/delete', 'UserController@deleteAccount')->name('deleteAccount');
		
		Route::post('/payment-settings', 'UserController@getPaymentSettings');
		Route::post('/payment-settings/default', 'UserController@updateDefaultPaymentSettings');
		Route::post('/payment-settings/bank', 'UserController@updateBankPaymentSettings');

		
		//PaypalController start		
		Route::post('/createPayPalPlan', 'PaypalController@createPayPalPlan')->name('createPayPalPlan');		
		Route::post('/updatePayPalPlan', 'PaypalController@updatePayPalPlan')->name('updatePayPalPlan');
		Route::post('/createSubscription', 'PaypalController@createSubscription')->name('createSubscription');	
		Route::post('/UpdatePlan', 'PaypalController@UpdatePlan')->name('UpdatePlan');
		Route::post('/cancelSubscription', 'PaypalController@cancelSubscription')->name('cancelSubscription');
		

		//PaypalController end
		
		
		
		//CustomerController start		
		Route::get('/customers/{id}', 'CustomerController@getCustomerById')->name('customers.show');

		Route::post('/addCustomer', 'CustomerController@addCustomer')->name('addCustomer');
		Route::post('/customers', 'CustomerController@getCustomers')->name('customers.list');
		Route::put('/customers/{id}', 'CustomerController@updateCustomer')->name('customers.update');
		Route::delete('/customers/{id}', 'CustomerController@deleteCustomer')->name('customers.delete');
		Route::patch('/customers/{id}/toggle-status', 'CustomerController@toggleCustomerStatus')->name('customers.toggleStatus');
		Route::get('/getExemptVats', 'CustomerController@getExemptVats')->name('customers.list');
		//CustomerController end
		
		//EmployeeController start		
//		Route::post('/addEmployee', 'EmployeeController@addEmployee')->name('addEmployee');
//		Route::get('/employees', 'EmployeeController@getEmployees')->name('getEmployees');
		

		Route::post('/addEmployee', 'EmployeeController@addEmployee')->name('addEmployee');
		Route::get('/employees', 'EmployeeController@getEmployees')->name('getEmployees');
		Route::put('/employee/{id}', 'EmployeeController@updateEmployee')->name('updateEmployee');
		Route::patch('/employee/status/{id}', 'EmployeeController@updateEmployeeStatus')->name('updateEmployeeStatus');
		Route::delete('/employee/{id}', 'EmployeeController@deleteEmployee')->name('deleteEmployee');
		Route::post('/employee/{id}/resend-invite', 'EmployeeController@resendInvite')
    ->middleware('auth:api');
		
		Route::post('/employeeDetails', 'EmployeeController@employeeDetails')->name('employeeDetails');
		// ✅ Route
		Route::delete('/employee/{id}', 'EmployeeController@deleteEmployee')->name('deleteEmployee');
		Route::delete('/company/{companyId}/remove', 'EmployeeController@removeCompany')->name('removeCompany');


		//EmployeeController end
		
		
		
		
		// Categories
		Route::get('/categories/active', [CategoryController::class, 'active'])
    ->name('categories.active');
		Route::post('/categoryList', 'CategoryController@categoryList')->name('categoryList');
		Route::post('/categories', 'CategoryController@store')->name('categories.store');
		Route::get('/categories/{id}', 'CategoryController@show')->name('categories.show');
		Route::put('/categories/{id}', 'CategoryController@update')->name('categories.update');
		//Route::delete('/categories/{id}', 'CategoryController@destroy')->name('categories.delete');
		Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.delete');
		Route::patch('/categories/{id}/toggle-status', 'CategoryController@toggleStatus')->name('categories.toggleStatus');


		// Products
		Route::post('/productsList', [ProductController::class, 'productsList'])->name('productsList');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.delete');
        Route::patch('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggleStatus');
		
		Route::get('/quantity-units', [ProductController::class, 'quantityUnits'])->name('products.list');

		Route::get('/vats', [ProductController::class, 'vats'])->name('products.list');

		
		
		
		Route::get('/paypal/success', function () {
			return response()->json(['message' => 'Payment Successful']);
		})->name('paypal.success');

		Route::get('/paypal/cancel', function () {
			return response()->json(['message' => 'Payment Cancelled']);
		})->name('paypal.cancel');
		
		

		
		
    });

