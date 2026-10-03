<?php

namespace Modules\User\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\Home\app\Models\Language;
use Modules\User\app\Models\Country;
use Modules\User\app\Models\IcelandPostalCodes;
use Modules\User\app\Models\User;
use Modules\User\app\Models\SubscriptionPlan;
use Modules\User\app\Models\Payment;
use Modules\User\app\Models\Customer;
use Modules\User\app\Models\UserDeleteReason;
	
use Illuminate\Support\Facades\Auth;

use Modules\User\app\Http\Requests\UserRequest;
use Modules\User\app\Http\Requests\LoginUserRequest;
use Modules\User\app\Http\Requests\UpdatePasswordRequest;
use Modules\User\app\Http\Requests\ChangePasswordRequest;
use Modules\User\app\Http\Services\UserService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use Illuminate\Database\Eloquent\SoftDeletes;


use Modules\User\app\Http\Services\PayPalService;

class UserController extends Controller
{
    private UserService $userService;
	private PayPalService $payPalService;

    public function __construct(UserService $userService, PayPalService $payPalService)
    {
        $this->userService = $userService;
		$this->payPalService = $payPalService;
    }
	
	public function getAllLanguage($lang): JsonResponse
	{
    // Validate input (only 'en' or 'is' allowed)
    if (!in_array($lang, ['en', 'is'])) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid language code. Use "en" or "is".'
        ], 400);
    }

    // Select the correct column based on lang
    $column = $lang === 'en' ? 'en_language' : 'is_language';

    // Fetch all languages (origin_text + selected column)
    $languages = Language::select('origin_text', $column . ' as translation')
        ->orderBy('id', 'asc')
        ->get();

    // Return JSON response
    return response()->json([
        'status' => true,
        'language' => $lang,
        'count' => $languages->count(),
        'data' => $languages
    ]);
}
	
	
	public function getPaymentSettings(Request $request): JsonResponse
	{
		$user = auth()->user();
		$companyId = $request->input('company_id'); // selected company for employees
		$language = $request->header('Accept-Language', 'en'); 

		// CASE 1: Logged-in user is a COMPANY (company_id is empty)
		if ($companyId == '') {
			// Fetch payment settings for the company
			$paymentSetting = $user->paymentSetting()
				->with('employeesWithRights:id,company_name,email')
				->first();

			if (!$paymentSetting) {
				return response()->json([
					'status' => false,
					'message' => __t('No payment settings found for this company', $language),
					'settings' => null,
				], 404);
			}

			return response()->json([
				'status' => true,
				'message' => __t('Payment settings fetched successfully', $language),
				'settings' => $paymentSetting,
			]);
		} 
		// CASE 2: Logged-in user is an EMPLOYEE (company_id is provided)
		else {
			if (!$companyId) {
				return response()->json([
					'status' => false,
					'message' => __t('Company ID is required for employees', $language),
				], 400);
			}

			// Find pivot record for this employee–company relationship
			$pivot = \DB::table('company_employees')
				->where('company_id', $companyId)
				->where('employee_id', $user->id)
				->first();

			if (!$pivot) {
				return response()->json([
					'status' => false,
					'message' => __t('You are not associated with this company', $language),
				], 403);
			}

			// Get the company user
			$company = User::find($companyId);

			if (!$company) {
				return response()->json([
					'status' => false,
					'message' => __t('Company not found', $language),
				], 404);
			}

			// Fetch payment settings for the company
			$paymentSetting = $company->paymentSetting()
				->with('employeesWithRights:id,company_name,email')
				->first();

			if (!$paymentSetting) {
				return response()->json([
					'status' => false,
					'message' => __t('No payment settings found for this company', $language),
					'settings' => null,
				], 404);
			}

			// Check if employee has payment rights
			$hasPaymentRights = false;
			if ($paymentSetting->employees_with_rights) {
				$employeesWithRights = json_decode($paymentSetting->employees_with_rights, true);
				$hasPaymentRights = is_array($employeesWithRights) && in_array($user->id, $employeesWithRights);
			}

			return response()->json([
				'status' => true,
				'message' => __t('Payment settings fetched successfully', $language),
				'settings' => $paymentSetting,
				'has_payment_rights' => $hasPaymentRights, // Indicate if employee has rights
			]);
		}			
	}
	
	public function _18_10_2025getPaymentSettings(Request $request): JsonResponse
	{
		$user = auth()->user(); // company

		// Fetch payment settings with related employees
		$paymentSetting = $user->paymentSetting()
			->with('employeesWithRights:id,company_name,email')
			->first();

		if (!$paymentSetting) {
			return response()->json([
				'status' => false,
				'message' => 'No payment settings found for this company',
				'settings' => null,
			], 404);
		}

		return response()->json([
			'status' => true,
			'message' => 'Payment settings fetched successfully',
			'settings' => $paymentSetting,
		]);
	}


    
	public function updateDefaultPaymentSettings(Request $request): JsonResponse
	{
		$user = auth()->user(); // company

		$language = $request->header('Accept-Language', 'en');
		$validator = Validator::make($request->all(), [
			'default_action' => 'required',//|in:Send To Bank,manual,other',
			'employees_with_rights' => 'nullable|array',
			'employees_with_rights.*' => 'exists:users,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}

		$paymentSetting = $user->paymentSetting()->updateOrCreate(
			['company_id' => $user->id],
			$request->only(['default_action'])
		);

		$paymentSetting->employeesWithRights()->sync($request->employees_with_rights ?? []);

		return response()->json([
			'status' => true,
			'message' => __t('Default payment settings updated successfully', $language ?? 'en'),
			'settings' => $paymentSetting->load('employeesWithRights:id,company_name,email'),
		]);
	}

	public function updateBankPaymentSettings(Request $request): JsonResponse
	{
    $user = auth()->user(); // company

	$language = $request->header('Accept-Language', 'en');
    $validator = Validator::make($request->all(), [
        'bank_no' => 'required|string',
        'bank_identifier' => 'required|string',
        'due_date_rule' => 'nullable|string',
        'final_due_date_rule' => 'nullable|string',
        'cancellation_date_rule' => 'nullable|string',
        'payment_currency' => 'required|string'
    ],
    [
        'bank_no.required' => __t('bank_no_required', $language),
        'bank_no.string' => __t('bank_no_invalid', $language),
        'bank_identifier.required' => __t('bank_identifier_required', $language),
        'bank_identifier.string' => __t('bank_identifier_invalid', $language),
        'payment_currency.required' => __t('payment_currency_required', $language),
        'payment_currency.string' => __t('payment_currency_invalid', $language)
    ]);
		

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    $paymentSetting = $user->paymentSetting()->updateOrCreate(
        ['company_id' => $user->id],
        $request->only([
            'bank_no',
            'bank_identifier',
            'due_date_rule',
            'final_due_date_rule',
            'cancellation_date_rule',
            'payment_currency',
        ])
    );

    return response()->json([
        'status' => true,
        'message' => __t('Bank and payment request settings updated successfully', $language ?? 'en'),
        'settings' => $paymentSetting,
    ]);
}


	
	public function deleteAccount(Request $request): JsonResponse
	{
		$user = Auth::user();
		$language = $request->header('Accept-Language', 'en');
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => __t('User not authenticated', $language ?? 'en'),
			], 401);
		}

		try {
			// Capture reason
			$reason = $request->input('reason');

			// Store reason in separate table
			UserDeleteReason::create([
				'user_id' => $user->id,
				'reason'  => $reason,
			]);

			// Soft delete the user
			$user->delete();

			return response()->json([
				'status'  => true,
				'message' => __t('Account deleted successfully (soft deleted)', $language ?? 'en'),
				'reason'  => $reason,
			], 200);
		} catch (\Exception $e) {
			return response()->json([
				'status'  => false,
				'message' => __t('Failed to delete account', $language ?? 'en'),
				'error'   => $e->getMessage(),
			], 500);
		}
	}

	
	public function forgotPassword(Request $request)
	{
    // Automatically detect language (from header or default 'en')
    $language = $request->header('Accept-Language', 'en');

    // Validation with multilingual messages
    $validator = Validator::make(
        $request->all(),
        [
            'email' => 'required|email|exists:users,email',
        ],
        [
            'email.required' => __t('email_required', $language),
            'email.email' => __t('email_invalid', $language),
            'email.exists' => __t('email_not_found', $language),
        ]
    );

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    $user = User::where('email', $request->email)->first();

    // Generate reset token
    $token = Str::random(64);
    $user->reset_token = $token;
    $user->reset_token_created_at = now();
    $user->save();

    // Reset link
    $resetUrl = env('APP_URL') . "/auth/reset-password/" . $token;
    $site_url = env('WEB_URL');

    $to = $user->email;
    $subject = __t('password_reset_subject', $language);
    $message = view('email.forgot_password', [
        'companyName' => $user->company_name,
        'resetUrl' => $resetUrl,
        'site_url' => $site_url
    ])->render();

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";

    if (!mail($to, $subject, $message, $headers)) {
        return response()->json([
            'status' => false,
            'message' => __t('token_generated_email_failed', $language),
        ], 500);
    }

    return response()->json([
        'status' => true,
        'message' => __t('passwordresetlinksenttoyouremail', $language),
    ]);
}




	public function resetPassword(Request $request, $token)
	{
		$language = $request->header('Accept-Language', 'en');

		// Validate password with translation messages
		$validator = Validator::make(
			$request->all(),
			[
				'password' => 'required|min:6|confirmed',
			],
			[
				'password.required' => __t('password_required', $language),
				'password.min' => __t('password_min_length', $language),
				'password.confirmed' => __t('password_confirmation_mismatch', $language),
			]
		);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}

		$user = User::where('reset_token', $token)->first();

		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => __t('invalid_or_expired_token', $language),
			], 400);
		}

		// Optional — check expiry (valid for 1 hour)
		if ($user->reset_token_created_at < now()->subHour()) {
			return response()->json([
				'status' => false,
				'message' => __t('token_expired', $language),
			], 400);
		}

		// Update password
		$user->password = Hash::make($request->password);
		$user->reset_token = null;
		$user->reset_token_created_at = null;
		$user->save();

		return response()->json([
			'status' => true,
			'message' => __t('password_updated_successfully', $language),
		]);
	}






	
	public function OrderHistory(Request $request): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();
        // Validate input
			//        $validator = Validator::make($request->all(), [
			//        	'active_plan_id' => 'required|string',
			//			'subscription_id' => 'nullable|string',
			//			'order_id' => 'nullable|string',
			//            //'user_id'    => 'required|integer'
			//        ]);
			//
			//        if ($validator->fails()) {
			//            return response()->json([
			//                'status' => false,
			//                'errors' => $validator->errors()->first(),
			//            ]);
			//        }


        $users = User::find($user->id);

        if (!$users) {
            return response()->json([
                'status' => false,
                'errors' => __t('Invalid ID provided', $language),
            ], 404);
        }
		
		
		
				//		$users->subscription_id = $request->subscription_id;
				//		$users->active_plan_id = $request->active_plan_id;
				//		$users->plan_activate_date = date("Y-m-d");
				//		$users->plan_expiry_date = date("Y-m-d", strtotime("+30 days"));
				//		$users->status = 1;
				//        $users->save();
		
				
			$oder_history = Payment::select()->where('user_id', $user->id)->get()->toArray();
			
		
		//$token = $user->createToken('UserToken')->accessToken;
 		$data_array=[
			'oder_history'=>$oder_history,
			'plan_name'=>$users->active_plan_name,
			'active_plan_price'=>$users->active_plan_price,
			'subscription_id'=>$users->subscription_id,
			'renewal_date'=>$users->plan_expiry_date,
			'is_cancel_plan'=>$users->is_cancel_plan,
		];
        return response()->json([
            'status' => true,
			//'access_token' => $token,
            //'token_type' => 'Bearer',
			
            'data' => $data_array, // Corrected response
            'message' => '',
        ]);


    }	
	
	
	public function user()
     {
		$language = $request->header('Accept-Language', 'en');
         $user = Auth::user();
		
		if ($user && $user->profile_logo) {
			$user->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $user->profile_logo;
		}
         return response()->json([
			 'status' => true,
             'user' => $user,
         ]);
     }
	
	public function editUserDetails(Request $request)
     {
		$language = $request->header('Accept-Language', 'en');
         $user = Auth::user();

         $validator = Validator::make($request->all(), [
             'social_security_number' => [
					'nullable',
					'string',
					'max:255',
					Rule::unique('users', 'social_security_number')->ignore($user->id),
				],
             'contact_person_name'     => 'nullable|string|max:255',
			 'company_registry_number'    => 'nullable|string|max:255',
             'company_name'     => 'nullable|string|max:255',
			 'address'    => 'nullable|string|max:255',
             'language'     => 'nullable|string|max:255',
			 'vat' => 'nullable|string|min:4|max:6',
             'postal_code'     => 'nullable|string|max:255',
			 'currency'    => 'nullable|string|max:255',
             'country'     => 'nullable|string|max:255',
             //'email'         => 'nullable|email|unique:users,email,' . $user->id,
             //'phone_code'      => 'required|string|max:255',
             //'phone_number'  => 'required|string|max:20|regex:/^[0-9+\-() ]+$/',
			 'city'      => 'nullable|string|max:255',
             'type_of_company'      => 'nullable|string|max:255',
			 'profile_logo'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
         ]);

         if ($validator->fails()) {
             return response()->json([
				 'status' => false,
                 'errors' => $validator->errors()->first(),
             ], 422);
         }
		
		$data = User::find($user->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'errors' => __t('Invalid ID provided', $language),
            ], 404);
        }
		
		$data->social_security_number = $request->social_security_number ?? $user->social_security_number;
		$data->contact_person_name = $request->contact_person_name ?? $user->contact_person_name;
		$data->company_registry_number = $request->company_registry_number ?? $user->company_registry_number;
		$data->company_name = $request->company_name ?? $user->company_name;
		$data->address = $request->address ?? $user->address;
		$data->language = $request->language ?? $user->language;
		$data->vat = $request->vat ?? $user->vat;
		$data->postal_code = $request->postal_code ?? $user->postal_code;
		$data->currency = $request->currency ?? $user->currency;
		$data->country = $request->country ?? $user->country;
		//$data->email = $request->email ?? $user->email;
		$data->type_of_company = $request->type_of_company ?? $user->type_of_company;
		$data->city = $request->city ?? $user->city;
		//$data->phone_code = $request->phone_code ?? $user->phone_code;
		//$data->phone_number = $request->phone_number ?? $user->phone_number;
		
		if ($request->hasFile('profile_logo')) {
			// ðŸ”¹ Delete old logo if exists
			if (!empty($data->profile_logo)) {
				// Extract only the filename from the URL
				$filename = basename($data->profile_logo);
				$oldPath = public_path('uploads/profile_logos/' . $filename);

				if (file_exists($oldPath)) {
					unlink($oldPath);
				}
			}
			// ðŸ”¹ Upload new logo
			$image = $request->file('profile_logo');
			$filename = time() . '_' . $image->getClientOriginalName();
			$destinationPath = public_path('uploads/profile_logos');

			if (!file_exists($destinationPath)) {
				mkdir($destinationPath, 0755, true);
			}

			$image->move($destinationPath, $filename);

			// Save relative path in DB
			$data->profile_logo = $filename;
		}else{
			$filename=$user->profile_logo;
		}



		
         $data->save();
		if ($data && $data->profile_logo) {
			$data->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $data->profile_logo;
		}
         //$data->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $filename;

         return response()->json([
			 'status' => true,
             'message' => __t('User details updated successfully', $language),
             'data' => $data,
         ]);
     }
	
	
	public function editUserBankAndGlnDetails(Request $request)
	{
		$language = $request->header('Accept-Language', 'en');
		$user = Auth::user();

		$validator = Validator::make($request->all(), [
			'bank_name'              => 'nullable|string|max:255',
			'account_number'         => 'nullable|string|max:255',
			'invoice_in_online_bank_misc' => 'nullable|boolean',
			'invoice_in_online_bank' => 'nullable|boolean',
			'show_acc_no_invoice' => 'nullable|boolean',
			'connect_national_register' => 'nullable|boolean',
			'access_key'             => 'nullable|string|max:255',
			'gln_username'           => 'nullable|string|max:255',
			'gln_password'           => 'nullable|string|max:255',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}

		$data = User::find($user->id);
		if (!$data) {
			return response()->json([
				'status' => false,
				'errors' => __t('Invalid ID provided', $language),
			], 404);
		}

		$data->bank_name               = $request->bank_name ?? $user->bank_name;
		$data->account_number          = $request->account_number ?? $user->account_number;
		$data->invoice_in_online_bank_misc  = $request->invoice_in_online_bank_misc ?? $user->invoice_in_online_bank_misc;	
		$data->invoice_in_online_bank  = $request->invoice_in_online_bank ?? $user->invoice_in_online_bank;
		$data->show_acc_no_invoice  = $request->show_acc_no_invoice ?? $user->show_acc_no_invoice;	
		$data->connect_national_register = $request->connect_national_register ?? $user->connect_national_register;
		$data->access_key              = $request->access_key ?? $user->access_key;
		$data->gln_username            = $request->gln_username ?? $user->gln_username;
		$data->gln_password            = $request->gln_password ?? $user->gln_password;

		$data->save();

		return response()->json([
			'status' => true,
			'message' => __t('Bank, MISC & GLN details updated successfully', $language),
			'data' => $data,
		]);
	}

	
	public function changePassword(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en');
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __t('Unauthorized. Please login first.'),
        ], 401);
    }

    // Validate
    $validator = Validator::make($request->all(), [
        'current_password' => 'required',
        'new_password' => 'required|string|min:8|confirmed',
    ], [
        'new_password.confirmed' => __t('New password and confirmation password must match.'),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    // Verify current password
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => __t('Current password is incorrect'),
        ], 400);
    }

    // Update password
    $user->update([
        'password' => Hash::make($request->new_password),
    ]);

    return response()->json([
        'status' => true,
        'message' => __t('passwordchangedsuccessfully'),
    ]);
}

	
	
	
	
	public function getPlansWithFeatures(): JsonResponse
	{
		$plans = SubscriptionPlan::with(['features' => function ($query) {
			$query->orderBy('id');
		}])->where('is_active', 1)->get();

		return response()->json([
			'status' => true,
			'data' => $plans
		]);
	}


	public function getCountryList(): JsonResponse
	{
		$countries = Country::orderBy('name')->get();

		return response()->json([
			'status' => true,
			'data' => $countries,
		]);
	}
	
	public function getPostalCodesList(): JsonResponse
	{
		$PostalCodes = IcelandPostalCodes::orderBy('id')->get();

		return response()->json([
			'status' => true,
			'data' => $PostalCodes,
		]);
	}
		
	public function MoreAboutYourCompany(Request $request): JsonResponse
	{
    // Get language from header (default to English)
    $language = $request->header('Accept-Language', 'en');

    // Validate input
    $validator = Validator::make($request->all(), [
        'contact_person_name' => 'required|string|max:30',
        'address'             => 'required|string',
        'postal_code'         => 'required|digits_between:3,10',
        'country'             => 'required|string',
        'user_id'             => 'required|integer',
    ], [
        'contact_person_name.required' => __t('Contact person name is required', $language),
        'address.required'             => __t('Address is required', $language),
        'postal_code.required'         => __t('Postal code is required', $language),
        'country.required'             => __t('Country is required', $language),
        'user_id.required'             => __t('User ID is required', $language),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    // Find user
    $allitems = User::find($request->user_id);

    if (!$allitems) {
        return response()->json([
            'status' => false,
            'errors' => __t('Invalid ID provided', $language),
        ], 404);
    }

    // Optional: Auto fill city from postal code
    $PostalCodes = IcelandPostalCodes::where('postal_code', $request->postal_code)->first();

    $allitems->contact_person_name = $request->contact_person_name;
    $allitems->address             = $request->address;
    $allitems->postal_code         = $request->postal_code;
    $allitems->country             = $request->country;

    if ($PostalCodes) {
        $allitems->city = $PostalCodes->location_name;
    }

    $allitems->save();

    return response()->json([
        'status'  => true,
        'data'    => $allitems,
        'message' => __t('Updated successfully', $language),
    ]);
}

	
	public function SetupPassword(Request $request): JsonResponse
	{
    $language = $request->header('Accept-Language', 'en');

    // Validate input with translated messages
    $validator = Validator::make($request->all(), [
        'password' => [
            'required',
            'string',
            'min:6',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
        ],
        'user_id' => 'required|integer',
    ], [
        'password.required'  => __t('password_required', $language),
        'password.min'       => __t('password_min_length', $language),
        'password.confirmed' => __t('password_confirmation_mismatch', $language),
        'password.regex'     => __t('password_complexity_failed', $language),
        'user_id.required'   => __t('User ID is required', $language),
        'user_id.integer'    => __t('user_id_integer', $language),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    $allitems = User::find($request->user_id);

    if (!$allitems) {
        return response()->json([
            'status' => false,
            'errors' => __t('Invalid ID provided', $language),
        ], 404);
    }

    $allitems->password = bcrypt($request->password);
    $allitems->save();

    return response()->json([
        'status' => true,
        'data' => $allitems,
        'message' => __t('password_updated_successfully', $language),
    ]);
}

	
	
	public function CheckVerificationCode(Request $request): JsonResponse
	{
    $language = $request->header('Accept-Language', 'en');

    // ? Validation with translated messages
    $validator = Validator::make($request->all(), [
        'verification_code' => 'required|string',
        'user_id' => 'required|string',
    ], [
        'verification_code.required' => __t('verification_code_required', $language),
        'user_id.required' => __t('User ID is required', $language),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    // ? Check if user and code match
    $user = User::where('verification_code', $request->verification_code)
                ->where('id', $request->user_id)
                ->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __t('verification_code_mismatch', $language),
        ], 422);
    }

    return response()->json([
        'status' => true,
        'message' => __t('verification_code_success', $language),
    ]);
}


	
    public function CreateAccount(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en');

		// ? Validation rules and translated messages
		$validator = Validator::make($request->all(), [
			'social_security_number' => 'required|string|unique:users,social_security_number',
			'company_name'           => 'required|string',
			'email'                  => 'required|email|max:255',
			'phone_code'             => 'required|string|max:4',
			'phone_number'           => 'required|string|max:10',
			'vat'                    => 'nullable|string|min:4|max:6',
		], [
			'social_security_number.required' => __t('socialsecuritynumberisrequired', $language),
			'social_security_number.unique'   => __t('social_security_number_unique', $language),
			'company_name.required'           => __t('companynameisrequired', $language),
			'email.required'                  => __t('email_required', $language),
			'email.email'                     => __t('email_invalid', $language),
			'email.max'                       => __t('email_too_long', $language),
			'phone_code.required'             => __t('phonecodeisrequired', $language),
			'phone_number.required'           => __t('phonenumberisrequired', $language),
			'vat.min'                         => __t('vat_min_length', $language),
			'vat.max'                         => __t('vat_max_length', $language),
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}

		$otp = rand(1000, 9999);
		$unique_id = $this->generateUniqueId();

		try {
			// Check if an inactive user exists with same email
			$existingUser = User::where('email', $request->email)->where('status', 0)->first();

			if ($existingUser) {
				// Update existing inactive user
				$user_data = $existingUser;
				$user_data->user_id = $unique_id;
			} else {
				// Check if email is already taken by an active user
				$activeUserExists = User::where('email', $request->email)->exists();
				if ($activeUserExists) {
					return response()->json([
						'status' => false,
						'errors' => 'Email already exists.',
					], 422);
				}
				
				$phoneExists = User::where('phone_code', $request->phone_code)
                              ->where('phone_number', $request->phone_number)
                              ->exists();
				if ($phoneExists) {
					return response()->json([
						'status' => false,
						'errors' => __t('Phone number already exists.', $language),
					], 422);
				}

				// Create new user
				$user_data = new User;
				$user_data->user_id = $unique_id;
			}


					$dst = '+' . $request->phone_code . '' . $request->phone_number;
					
					//$text = 'Your otp is ' . $otp . '';
			$text = 'Your verification code is: ' . $otp . '. Enter this code in the registration step of X-US Invoice System to verify your mobile number. If you did not request this, please ignore this message.';
			
					$url = "https://gatewayapi.com/rest/mtsms";
					$api_token = "GdTeudXVT-agezR8IPkm38p5TYZdA1wAHP71uu-L5rK9hiPyuQ9QL0HP886d4i4W";

	//Set SMS recipients and content
					$recipients = [];
					array_push($recipients, $dst);
					$json = [
						'sender' => 'X-US',
						//'sender' => 'HH Radgjof',
						'message' => $text,
						'recipients' => [],
					];
					foreach ($recipients as $msisdn) {
						$json['recipients'][] = ['msisdn' => $msisdn];
					}

	//Make and execute the http request
	//Using the built-in 'curl' library
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
					curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$result = curl_exec($ch);
					curl_close($ch);

			// Set/update user data
			$user_data->social_security_number = $request->social_security_number;
			$user_data->company_name = $request->company_name;
			$user_data->email = $request->email;
			$user_data->phone_code = $request->phone_code;
			$user_data->phone_number = $request->phone_number;
			$user_data->vat = $request->vat;
			$user_data->role = 'company';
			$user_data->verification_code = $otp;
			$user_data->save();

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'error' => $e->getMessage(),
			], 500);
		}

		return response()->json([
			'status' => true,
			'user' => $user_data,
			'verification_code' => $otp,
			'message' => __t('employeeaccountcreatedsuccessfully', $language),
		]);
}
	
	public function CreateAccount_Update(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en');
			$validator = Validator::make($request->all(), [
				'social_security_number' => [
					'required',
					'string',
					Rule::unique('users', 'social_security_number')->ignore($request->user_id, 'id'),
				],
				'company_name' => 'required|string',
				'email' => [
					'required',
					'email',
					'max:255',
					Rule::unique('users', 'email')->ignore($request->user_id, 'id'),
				],
				'phone_code'   => 'required|string|max:4',
				'phone_number' => 'required|string|max:10',
				'vat'          => 'nullable|string|min:4|max:6',
				'user_id'      => 'required|integer',
			], [
				'social_security_number.required' => __t('socialsecuritynumberisrequired', $language),
				'social_security_number.unique'   => __t('social_security_number_unique', $language),
				'company_name.required'           => __t('companynameisrequired', $language),
				'email.required'                  => __t('email_required', $language),
				'email.email'                     => __t('email_invalid', $language),
				'email.unique'                    => __t('email_unique', $language),
				'phone_code.required'             => __t('phonecodeisrequired', $language),
				'phone_number.required'           => __t('phonenumberisrequired', $language),
				'vat.min'                         => __t('vat_min_length', $language),
				'vat.max'                         => __t('vat_max_length', $language),
				'user_id.required'                => __t('User ID is required', $language),
			]);


			if ($validator->fails()) {
				return response()->json([
					'status' => false,
					'errors' => $validator->errors()->first(),
				], 422);
			}

			$otp = rand(1000, 9999);
			$unique_id = $this->generateUniqueId();

			try {
				// Check if an inactive user exists with same email
				$existingUser = User::where('id', $request->user_id)->where('status', 0)->first();

				if ($existingUser) {
					// Update existing inactive user
					
					
					
					$phoneExists = User::where('phone_code', $request->phone_code)
                              ->where('phone_number', $request->phone_number)
                              ->where('id', '!=', $request->user_id)
                              ->exists();
					if ($phoneExists) {
						return response()->json([
							'status' => false,
							'errors' => __t('phone_already_exists', $language),
						], 422);
					}
					
					$user_data = $existingUser;
					//$user_data->user_id = $unique_id;
				} else {
					// Check if email is already taken by an active user
					//$activeUserExists = User::where('email', $request->email)->exists();
					//if ($activeUserExists) {
						return response()->json([
							'status' => false,
							'errors' => __t('user_not_found_or_active', $language),
						], 422);
				   // }

					// Create new user
					//$user_data = new User;
					//$user_data->user_id = $unique_id;
				}
				
				
				
				
				$dst = '+' . $request->phone_code . '' . $request->phone_number;
                //echo $dst;
                $text = 'Your verification code is: ' . $otp . '. Enter this code in the registration step of X-US Invoice System to verify your mobile number. If you did not request this, please ignore this message.';
				
                $url = "https://gatewayapi.com/rest/mtsms";
                $api_token = "GdTeudXVT-agezR8IPkm38p5TYZdA1wAHP71uu-L5rK9hiPyuQ9QL0HP886d4i4W";

//Set SMS recipients and content
                $recipients = [];
                array_push($recipients, $dst);
                $json = [
                    //'sender' => 'HH Radgjof',
					'sender' => 'X-US',
                    'message' => $text,
                    'recipients' => [],
                ];
                foreach ($recipients as $msisdn) {
                    $json['recipients'][] = ['msisdn' => $msisdn];
                }

//Make and execute the http request
//Using the built-in 'curl' library
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);

				// Set/update user data
				$user_data->social_security_number = $request->social_security_number;
				$user_data->company_name = $request->company_name;
				$user_data->email = $request->email;
				$user_data->phone_code = $request->phone_code;
				$user_data->phone_number = $request->phone_number;
				$user_data->vat = $request->vat;
				$user_data->role = 'company';
				$user_data->verification_code = $otp;
				$user_data->save();

			} catch (\Exception $e) {
				return response()->json([
					'status' => false,
					'error' => $e->getMessage(),
				], 500);
			}

			return response()->json([
				'status' => true,
				'user' => $user_data,
				'verification_code' => $otp,
				'message' => __t('user_updated_successfully', $language),
			]);
}
	
	
	public function ResendOtp(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en');
			$validator = Validator::make($request->all(), [
				'user_id' => 'required|string'
			], [
				'user_id.required' => __t('user_id_required', $language),
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => false,
					'errors' => $validator->errors()->first(),
				], 422);
			}

			$otp = rand(1000, 9999);
			//$unique_id = $this->generateUniqueId();

			try {
				// Check if an inactive user exists with same email
				
				$user_data = User::where('id', $request->user_id)->first();
				//echo "<pre>";print_r($user_data);exit;
				
				$dst = '+' . $user_data->phone_code . '' . $user_data->phone_number;
				//echo $dst;exit;
                //echo $dst;
				//$text = 'Your otp is ' . $otp . '';
				$text = 'Your verification code is: ' . $otp . '. Enter this code in the registration step of X-US Invoice System to verify your mobile number. If you did not request this, please ignore this message.';

                //$text = 'Your verification code is: ' . $otp . ', Enter this code in the registration step of X-US Invoice System to verify your mobile number. If you didn’t request this, please ignore this message.';
				
                $url = "https://gatewayapi.com/rest/mtsms";
                $api_token = "GdTeudXVT-agezR8IPkm38p5TYZdA1wAHP71uu-L5rK9hiPyuQ9QL0HP886d4i4W";

//Set SMS recipients and content
                $recipients = [];
                array_push($recipients, $dst);
                $json = [
                    //'sender' => 'HH Radgjof',
					'sender' => 'X-US',
                    'message' => $text,
                    'recipients' => [],
                ];
                foreach ($recipients as $msisdn) {
                    $json['recipients'][] = ['msisdn' => $msisdn];
                }

//Make and execute the http request
//Using the built-in 'curl' library
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);
				//echo "<pre>";print_r($result);exit;
				
				
				
				$user_data->verification_code = $otp;
				$user_data->save();

			} catch (\Exception $e) {
				return response()->json([
					'status' => false,
					'error' => $e->getMessage(),
				], 500);
			}

			return response()->json([
				'status' => true,
				'user' => $user_data,
				'verification_code' => $otp,
				'message' => __t('otp_sent_success', $language),
			]);
}

	
	
	private function generateUniqueId(): string
	{
		do {
			$uniqueId = strtoupper(Str::random(8)); // e.g., "A8F7K2L9"
		} while (User::where('user_id', $uniqueId)->exists());

    	return $uniqueId;
	}
	
	public function getUserDetailsBeforeLogin(Request $request): JsonResponse
	{

		$user_id = $request->user_id; // all, active, inactive
		//echo $user_id;exit;
		$users = User::where('id', $user_id)->get();

		return response()->json([
			'status' => true,
			'data' => $users,
		]);
	}
	
	public function getUserDetails(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();

		if (!$user) {
			return response()->json([
				'status'  => false,
				'message' =>  __t('Unauthorized. Please login first', $language),//'Unauthorized. Please login first',
				'errors'  => [
					'auth' => [__t('User not authenticated', $language)]
				]
			], 401);
		}

		// load the relation (singular!)
		$user->load([
			'paymentSetting.employeesWithRights:id,company_name,email'
		]);

		// add profile_logo full path
		if ($user->profile_logo) {
			$user->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $user->profile_logo;
		}

		return response()->json([
			'status' => true,
			'data' => $user,
		]);
	}
	

	// public function signUp(UserRequest $request): JsonResponse
	//     {
	//         $response = $this->userService->signUp($request);

	//         return response()->json($response);
	//     }
    /**
     * Login
     *
     * @param LoginUserRequest $request
     * @return JsonResponse
     */
   
	
	public function login(Request $request): JsonResponse
	{		
   	
	$language = $request->header('Accept-Language', 'en'); 	

	$validator = Validator::make($request->all(), [
		'email' => 'required|email',
		'password' => 'required|string',
	], [
		'email.required' => __t('email_required', $language),
		'email.email' => __t('email_invalid', $language),
		'password.required' => __t('password_required', $language),
		'password.string' => __t('password_invalid', $language),
	]);


    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()->first()], 422);
    }

    $user = User::where('email', $request->email)->first();
		

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['status' => false, 'message' => __t('invalid_email_or_password', $language)], 401);
    }
		
	if ($user->profile_logo) {
		$user->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $user->profile_logo;
	}	

    // ? Get all companies where this user is an EMPLOYEE
    $employeeCompanies = $user->companies()
        ->select('users.id', 'users.company_name', 'users.vat','users.email', 'users.profile_logo', 'users.contact_person_name')
        ->withPivot('status')
        ->get();
		
		

    // ? If user is NOT under any company, companies should be null
    $allCompanies = $employeeCompanies->isNotEmpty() ? $employeeCompanies : null;

    // ? Create token for authentication
    $token = $user->createToken('authToken')->accessToken;

    // ? Default selected company (if any)
    $defaultCompany = $allCompanies ? $allCompanies->first() : null;
		
		if ($allCompanies) {
			foreach ($allCompanies as $company) {
				if (!empty($company->profile_logo)) {
					$company->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $company->profile_logo;
				}
			}
		}

		// ? Add full logo path for default company
		/*if ($defaultCompany && !empty($defaultCompany->profile_logo)) {
			$defaultCompany->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $defaultCompany->profile_logo;
		}*/

    // ? Return response
    return response()->json([
        'status' => true,
        //'message' => 'Login successful',
		'message' => __t('login_successful', $language),
        'token' => $token,
        'user' => $user,
        'companies' => $allCompanies,
        'company' => $defaultCompany,
    ]);
}




    /**
     * Activate Account
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activateAccount(Request $request): JsonResponse
    {
        $response = $this->userService->activateAccount($request);

        return response()->json($response);
    }



    /**
     * Update Password
     *
     * @param UpdatePasswordRequest $request
     * @return JsonResponse
     */
    


    /**
     * Change Password
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function updateDeviceid(Request $request): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en'); 	
		
        $user = auth()->user();
        if (empty($user)) {
            return response()->json([
                'res' => false,
                'msg' => __t('User is not authorized', $language),//'User is not authorized !',
                'data' => ""
            ]);
        }

        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
            'device_type' => 'required|string',
        ], [
			'device_token.required' => __t('device_token_required', $language),
			'device_type.required'  => __t('device_type_required', $language),
		]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first(),
            ]);
        }
        $user->device_token = $request->device_token;
        $user->device_type = $request->device_type;
        $user->save();


        return response()->json([
            'status' => true,
            'audio' => '', // Corrected response
            'message' => __t('Updated successfully', $language),
        ]);
    }

    /**
     * Change Password
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    

    /**
     * Log Out
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en'); 
        $user = auth()->user();
        if (empty($user)) {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $user->tokens()->delete();
        return response()->json(['message' => __t('Successfully logged out', $language)]);
    }

}
