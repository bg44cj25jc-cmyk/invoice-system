<?php

namespace Modules\User\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\User\app\Models\Country;
use Modules\User\app\Models\IcelandPostalCodes;
use Modules\User\app\Models\User;
use Modules\User\app\Models\SubscriptionPlan;
use Modules\User\app\Models\Payment;
use Modules\User\app\Models\Customer;

	
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

use Modules\User\app\Http\Services\PayPalService;

class EmployeeController extends Controller
{
    private UserService $userService;
	private PayPalService $payPalService;

    public function __construct(UserService $userService, PayPalService $payPalService)
    {
        $this->userService = $userService;
		$this->payPalService = $payPalService;
    }

	
	
	public function setupEmployeeAccount(Request $request, $token): JsonResponse
	{
		
	$language = $request->header('Accept-Language', 'en'); 	
    $pivot = \DB::table('company_employees')->where('invite_token', $token)->first();

    if (!$pivot) {
        return response()->json(['status' => false, 'message' => __t('invalid_or_expired_token', $language),], 404);
    }

    $employee = User::find($pivot->employee_id);

    if (!$employee) {
        return response()->json(['status' => false, 'message' => __t('employee_not_found', $language)], 404);
    }
		
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'phone_code' => 'required|string|max:4',
        'phone_number' => 'required|string|max:10',
        'password' => 'required|string|min:6|confirmed',
    ], [
        'name.required' => __t('name_required', $language),
        'phone_code.required' => __t('phonecodeisrequired', $language),
        'phone_number.required' => __t('phonenumberisrequired', $language),
        'password.required' => __t('passwordrequired', $language),
        'password.min' => __t('password_min_length', $language),
        'password.confirmed' => __t('password_must_match', $language),
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'errors' => $validator->errors()->first()], 422);
    }

    // Update global user profile
    $employee->update([
        'contact_person_name' => $request->name,
        'phone_code' => $request->phone_code,
        'phone_number' => $request->phone_number,
        'password' => Hash::make($request->password),
        'status' => 1,
    ]);

    // Activate pivot record
    \DB::table('company_employees')
        ->where('id', $pivot->id)
        ->update(['status' => 'Active', 'invite_token' => null]);

    return response()->json([
        'status' => true,
        'message' => __t('something_went_wrong', $language),
        'employee' => $employee,
    ]);
}


	
	public function getEmployeeByToken($token): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    // Find pivot record with this invite token
    $pivot = \DB::table('company_employees')->where('invite_token', $token)->first();

    if (!$pivot) {
        return response()->json([
            'status' => false,
            'message' => __t('invalid_or_expired_token', $language),
        ], 404);
    }

    // Get the employee and company from pivot relation
    $employee = User::find($pivot->employee_id);
    $company = User::find($pivot->company_id);

    if (!$employee || !$company) {
        return response()->json([
            'status' => false,
            'message' => __t('invalid_or_missing_employee_company_data', $language),
        ], 404);
    }

    return response()->json([
        'status' => true,
        'email' => $employee->email,
        'company_name' => $company->name ?? $company->company_name,
        'company_id' => $company->id,
        'data' => [
            'employee' => $employee,
            'pivot' => [
                'status' => $pivot->status,
                'invite_token' => $pivot->invite_token,
                'access_permissions' => $pivot->access_permissions ? json_decode($pivot->access_permissions, true) : [],
                'customer_permissions' => $pivot->customer_permissions ? json_decode($pivot->customer_permissions, true) : [],
                'created_at' => $pivot->created_at,
            ],
        ],
    ]);
}

	
	/////////////////////////////////////////////////
	
	
	
	
	////////////////////////////////////////////////////////
	
	public function addEmployee(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    $company = auth()->user();

    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'access_permissions' => 'required|array',
        'customers' => 'required|array',
    ], [
        'email.required' => __t('email_required', $language),
        'email.email' => __t('email_invalid', $language),
        'access_permissions.required' => __t('access_permissions_required', $language),
        'access_permissions.array' => __t('access_permissions_must_be_array', $language),
        'customers.required' => __t('customers_required', $language),
        'customers.array' => __t('customers_must_be_array', $language),
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    // 🚫 Prevent company from adding their own email
    if (strtolower($company->email) === strtolower($request->email)) {
        return response()->json([
            'status' => false,
            'message' => __t('cannot_add_own_email', $language),
        ], 422);
    }

    $employee = User::where('email', $request->email)->first();
    $token = Str::random(64);

    if ($employee) {
        if ($company->employees()->where('users.id', $employee->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => __t('employee_already_associated', $language),
            ], 422);
        }

        // Attach existing employee with pivot data
        $company->employees()->attach($employee->id, [
            'status' => 'Invitation Sent',
            'invite_token' => $token,
            'access_permissions' => json_encode($request->access_permissions),
            'customer_permissions' => json_encode($request->customers),
        ]);

        $this->sendInviteMail($employee, $company, $token);

        return response()->json([
            'status' => true,
            'message' => __t('invitation_sent_existing_employee', $language),
        ]);
    }

    // Create new employee record (global user)
    $employee = User::create([
        'email' => $request->email,
        'role' => 'employee',
        'status' => 0,
    ]);

    // Attach pivot data
    $company->employees()->attach($employee->id, [
        'status' => 'Invitation Sent',
        'invite_token' => $token,
        'access_permissions' => json_encode($request->access_permissions),
        'customer_permissions' => json_encode($request->customers),
    ]);

    $this->sendInviteMail($employee, $company, $token, $language);

    return response()->json([
        'status' => true,
        'message' => __t('employee_added_invitation_sent', $language),
        'employee' => $employee,
    ]);
}



    /**
     * ✅ Send Invitation Email
     */
    private function sendInviteMail($employee, $company, $token, $language)
	{
		$acceptUrl = env('APP_URL') . '/auth/employee/signup/' . $token;
		$site_url = env('WEB_URL');

		$to = $employee->email;
		$subject = __t("Invitation to join ", $language) . $company->company_name;
		$message = view('email.employee_invite', [
			'companyName' => $company->company_name,
			'acceptUrl' => $acceptUrl,
			'site_url' => $site_url
		])->render();

		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8\r\n";
		$headers .= "From: X-US Invoice System <no-reply@yourapp.com>\r\n";

		mail($to, $subject, $message, $headers);
	}
	
	
	public function resendInvite(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    $company = auth()->user();

    // 🔍 Find employee relationship
    $pivot = \DB::table('company_employees')
        ->where('company_id', $company->id)
        ->where('employee_id', $id)
        ->first();

    if (!$pivot) {
        return response()->json([
            'status' => false,
            'message' => __t('employee_not_found_in_company', $language),
        ], 404);
    }

    // 🔒 Allow resend only if current status is "Invitation Sent"
    if ($pivot->status !== 'Invitation Sent') {
        return response()->json([
            'status' => false,
            'message' => __t('invitation_can_only_be_resent_if_pending', $language),
        ], 422);
    }

    // 🆕 Generate new invite token each time for security
    $newToken = Str::random(64);

    // 🗄️ Update pivot
    \DB::table('company_employees')
        ->where('id', $pivot->id)
        ->update([
            'invite_token' => $newToken,
            'updated_at' => now(),
        ]);

    $employee = User::find($id);

    if (!$employee) {
        return response()->json([
            'status' => false,
            'message' => 'Employee user record not found.'
        ], 404);
    }

    // ✉️ Resend the invite email
    $this->sendInviteMail($employee, $company, $newToken, $language);

    return response()->json([
        'status' => true,
        'message' => 'Invitation email resent successfully.'
    ]);
}



    /**
     * ✅ Get all employees of logged-in company
     */
    public function getEmployees(Request $request): JsonResponse
	{
    $company = auth()->user();
    $filter = $request->query('filter', 'All'); // Default: All

    $employeesQuery = $company->employees()
        ->select('users.id', 'users.company_name', 'users.email', 'users.phone_code', 'users.phone_number', 'users.created_at')
        ->orderByDesc('users.id');

    // ✅ Apply filter if not "All"
    if ($filter !== 'All') {
        $employeesQuery->wherePivot('status', $filter);
    }

    $employees = $employeesQuery->get()->map(function ($emp) {
        $pivot = $emp->pivot;
        return [
            'id' => $emp->id,
            'name' => $emp->company_name ?? $emp->company_name,
            'email' => $emp->email,
            'phone_code' => $emp->phone_code,
            'phone_number' => $emp->phone_number,
            'status' => $pivot->status ?? 'Invitation Sent',
            'access_permissions' => $pivot->access_permissions ? json_decode($pivot->access_permissions, true) : [],
            'customer_permissions' => $pivot->customer_permissions ? json_decode($pivot->customer_permissions, true) : [],
            'invite_token' => $pivot->invite_token ?? null,
            'added_date' => optional($emp->created_at)->format('d/m/Y'),
        ];
    });

    return response()->json([
        'status' => true,
        'filter' => $filter,
        'employees' => $employees,
    ]);
}
	
	
	public function employeeDetails(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
		$user = auth()->user();
		
//    $validator = Validator::make($request->all(), [
//        'email' => 'required|email',
//        'password' => 'required|string',
//    ]);
//
//    if ($validator->fails()) {
//        return response()->json(['status' => false, 'errors' => $validator->errors()->first()], 422);
//    }

   // $user = User::where('email', $request->email)->first();
	if ($user->profile_logo) {
		$user->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $user->profile_logo;
	}	

//    if (!$user || !Hash::check($request->password, $user->password)) {
//        return response()->json(['status' => false, 'message' => 'Invalid email or password.'], 401);
//    }

    // ✅ Get all companies where this user is an EMPLOYEE
    $employeeCompanies = $user->companies()
        ->select('users.id', 'users.company_name', 'users.email', 'users.profile_logo')
        ->withPivot('status')
        ->get();
		
		

    // ✅ If user is NOT under any company, companies should be null
    $allCompanies = $employeeCompanies->isNotEmpty() ? $employeeCompanies : null;

    

    // ✅ Default selected company (if any)
    $defaultCompany = $allCompanies ? $allCompanies->first() : null;
		
		if ($allCompanies) {
			foreach ($allCompanies as $company) {
				if (!empty($company->profile_logo)) {
					$company->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $company->profile_logo;
				}
			}
		}

		// ✅ Add full logo path for default company
		/*if ($defaultCompany && !empty($defaultCompany->profile_logo)) {
			$defaultCompany->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $defaultCompany->profile_logo;
		}*/

    // ✅ Return response
    return response()->json([
        'status' => true,
        'message' => __t('login_successful', $language),
        'user' => $user,
        'companies' => $allCompanies,
        'company' => $defaultCompany,
    ]);
}


    /**
     * ✅ Edit Employee (permissions, name, etc.)
     */
    public function updateEmployee(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    $company = auth()->user();
    $employee = User::find($id);
    if (!$employee) {
        return response()->json(['status'=>false,'message'=> __t('employee_not_found', $language)], 404);
    }

    // ensure employee is associated with this company
    if (! $company->employees()->where('users.id', $employee->id)->exists()) {
        return response()->json(['status'=>false,'message'=> __t('employee_not_found_in_your_company', $language)], 404);
    }

    // update global user fields (name/phone)
    $employee->update($request->only(['company_name','name','phone_code','phone_number']));

    // update pivot fields (permissions)
    $pivotData = [];
    if ($request->has('access_permissions')) {
        $pivotData['access_permissions'] = json_encode($request->access_permissions);
    }
    if ($request->has('customer_permissions') || $request->has('customers')) {
        $pivotData['customer_permissions'] = json_encode($request->customer_permissions ?? $request->customers);
    }

    if (!empty($pivotData)) {
        $company->employees()->updateExistingPivot($employee->id, $pivotData);
    }

    return response()->json(['status'=>true,'message'=> __t('employeeupdatedsuccessfully', $language)]);
}


    /**
     * ✅ Change Employee Status (Active, Blocked)
     */
    public function updateEmployeeStatus(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
		$company = auth()->user();
		$employee = User::find($id);
		if (!$employee) return response()->json(['status'=>false,'message'=> __t('employee_not_found', $language)], 404);

		if (! $company->employees()->where('users.id', $employee->id)->exists()) {
			return response()->json(['status'=>false,'message'=> __t('employee_not_found_in_your_company', $language)], 404);
		}

		$validator = Validator::make($request->all(), [
			'status' => 'required|string', // ensure valid statuses on client or use enum rules
			'update_user_global' => 'nullable|boolean',
		]);

		if ($validator->fails()) {
			return response()->json(['status'=>false,'errors'=>$validator->errors()->first()], 422);
		}

		// update pivot
		$company->employees()->updateExistingPivot($employee->id, ['status' => $request->status]);

		// optionally update global user status
		if ($request->filled('update_user_global') && $request->update_user_global) {
			$employee->status = $request->status === 'Active' ? 1 : ($request->status === 'Blocked' ? 2 : $employee->status);
			$employee->save();
		}

		return response()->json(['status'=>true,'message'=> __t('employee_status_updated', $language)]);
	}

    /**
     * ✅ Delete Employee from Company (detach only)
     */
    public function deleteEmployee(Request $request, $id): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en'); 
        $company = auth()->user();

        if (!$company->employees()->where('users.id', $id)->exists()) {
            return response()->json(['status' => false, 'message' => __t('employee_not_found_in_your_company', $language)], 404);
        }

        $company->employees()->detach($id);

        return response()->json(['status' => true, 'message' => __t('employee_removed_from_company', $language)]);
    }
	
	public function removeCompany(Request $request, $companyId): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    $employee = auth()->user();

    // Ensure employee is part of this company
    if (!$employee->companies()->where('users.id', $companyId)->exists()) {
        return response()->json(['status' => false, 'message' => __t('company_not_found_in_your_list', $language)], 404);
    }

    $employee->companies()->detach($companyId);

    return response()->json(['status' => true, 'message' => __t('company_removed_successfully', $language)]);
}

	
	
	
	public function updateEmployeeStatusNotLogin(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    $validator = Validator::make($request->all(), [
        'status' => 'required|string|in:Invitation Sent,Active,Blocked',
        'invite_token' => 'required|string',
        'update_user_global' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    $employee = User::find($id);
    if (!$employee) {
        return response()->json(['status' => false, 'message' => __t('employee_not_found', $language)], 404);
    }

    // ✅ Find company using the employee’s invite token
    $companyEmployee = \DB::table('company_employees')
        ->where('employee_id', $id)
        ->where('invite_token', $request->invite_token)
        ->first();

    if (!$companyEmployee) {
        return response()->json([
            'status' => false,
            'message' => __t('invalid_or_expired_invite_token', $language)
        ], 404);
    }

    // ✅ Update status in pivot
    \DB::table('company_employees')
        ->where('employee_id', $id)
        ->where('invite_token', $request->invite_token)
        ->update([
            'status' => $request->status,
            'updated_at' => now()
        ]);

    // ✅ Optionally update global user status
    if ($request->filled('update_user_global') && $request->update_user_global) {
        $employee->status = $request->status === 'Active' ? 1 : ($request->status === 'Blocked' ? 2 : $employee->status);
        $employee->save();
    }

    return response()->json([
        'status' => true,
        'message' => __t('employeestatusupdatedsuccessfully', $language)
    ]);
}








}
