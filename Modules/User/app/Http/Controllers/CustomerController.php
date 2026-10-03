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
use Modules\User\app\Models\Category;
	
use Illuminate\Support\Facades\Auth;
use Modules\User\app\Models\ExemptVat;

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


class CustomerController extends Controller
{
    private UserService $userService;
	private PayPalService $payPalService;

    public function __construct(UserService $userService, PayPalService $payPalService)
    {
        $this->userService = $userService;
		$this->payPalService = $payPalService;
    }
	
	
	
	
	public function getExemptVats()
	{
		$vats = ExemptVat::where('status', 1)
			->orderBy('id')
			->get();

		return response()->json([
			'status' => true,
			'vats' => $vats
		]);
	}
	
	
	public function index()
    {
        $user = auth()->user();
        $categories = Category::where('user_id', $user->id)->get();

        return response()->json(['status' => true, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
		$language = $request->header('Accept-Language', 'en'); 	
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'discount' => $request->discount ?? 0,
            'status' => 1,
        ]);

        return response()->json(['status' => true, 'message' => __t('categorycreatedsuccessfully', $language), 'category' => $category], 201);
    }

    public function update(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en'); 
        $user = auth()->user();
        $category = Category::where('user_id', $user->id)->findOrFail($id);

        $category->update($request->only(['name', 'discount', 'status']));

        return response()->json(['status' => true, 'message' => __t('categoryupdatedsuccessfully', $language), 'category' => $category]);
    }

    public function destroy(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en'); 
        $user = auth()->user();
        $category = Category::where('user_id', $user->id)->findOrFail($id);

        $category->delete();

        return response()->json(['status' => true, 'message' => __t('categorydeletedsuccessfully', $language)]);
    }

    public function toggleStatus(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en'); 
        $user = auth()->user();
        $category = Category::where('user_id', $user->id)->findOrFail($id);

        $category->status = $category->status ? 0 : 1;
        $category->save();

        return response()->json(['status' => true, 'message' => __t('statusupdatedsuccessfully', $language), 'category' => $category]);
    }
	
	
	
	
	
	public function __10_10_2025_addCustomer(Request $request): JsonResponse
	{
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
        'ssn'       => 'required|string',
        'full_name' => 'required|string|max:255',
        'email'     => 'nullable|email|max:100',
        'address'   => 'required|string',
        'zip_code'  => 'required|max:100',
        'country'   => 'required|max:100',
        'currency'  => 'required|string',
        'company_id' => 'nullable|integer', // Required only for employees
    ], [
        'ssn.required' => 'SSN is required',
    ], [
        'zip_code' => 'postal code',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->first(),
        ], 422);
    }

    // Determine company context
    $companyId = null;

    if ($user->role === 'employee') {
        if (!$request->filled('company_id')) {
            return response()->json([
                'status' => false,
                'message' => 'Company ID is required for employees.',
            ], 422);
        }

        $companyId = $request->company_id;

        // Verify that employee belongs to the selected company
        $isLinked = \DB::table('company_employees')
            ->where('company_id', $companyId)
            ->where('employee_id', $user->id)
            ->exists();

        if (!$isLinked) {
            return response()->json([
                'status' => false,
                'message' => 'You are not associated with this company.',
            ], 403);
        }
    }

    // Check for duplicate SSN (scoped by employee + company)
    $exists = Customer::where('user_id', $user->id)
        ->when($companyId, function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->where('ssn', $request->ssn)
        ->exists();

    if ($exists) {
        return response()->json([
            'status' => false,
            'errors' => 'Customer with the same SSN already exists for this user/company.',
        ], 422);
    }

    try {
        // Get next customer ID (scoped by user + company)
        $nextUserCustomerId = Customer::where('user_id', $user->id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->max('user_customer_id') + 1;

        // Create customer
        $customer = Customer::create([
            'user_id'   => $user->id,
            'company_id'=> $companyId,
            'user_customer_id' => $nextUserCustomerId,
            'ssn'       => $request->ssn,
            'full_name' => $request->full_name,
            'address'   => $request->address,
            'zip_code'  => $request->zip_code,
            'country'   => $request->country,
            'currency'  => $request->currency,
            'discount'  => $request->input('discount', $request->input('discound', 0)),
            'discound'  => $request->input('discound', $request->input('discount', 0)),
            'vat'       => $request->vat ?? 0,
            'telephone' => $request->telephone,
            'mobile'    => $request->mobile,
            'email'     => $request->email,
            'number_per_unit' => $request->number_per_unit,
            'invoice_in_email' => $request->invoice_in_email ?? 0,
            'invoice_in_online_bank' => $request->invoice_in_online_bank ?? 0,
            'send_electronic_invoice' => $request->input('send_electronic_invoice', $request->input('send_elecctronic_invoice', 0)),
            'send_elecctronic_invoice' => $request->input('send_elecctronic_invoice', $request->input('send_electronic_invoice', 0)),
            'payment_method' => $request->payment_method,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Customer created successfully',
            'customer' => $customer,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
}

	
	public function addCustomer(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
		$user = auth()->user();
		
		$validator = Validator::make($request->all(), [
			'ssn'       => 'required|string',
			'company_id' => 'nullable',
			'full_name' => 'required|string|max:255',
			'email'     => 'nullable|email|max:100',
			'address'     => 'required|string',
			'zip_code'     => 'required|max:100',
			'country'     => 'required|max:100',
			'currency'     => 'required|string',
		], [
			// Custom messages (optional, if you want to redefine rule messages)
		], [
			// Attribute replacements
			'zip_code' => 'postal code',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}

		// Check if same SSN + user_id already exists
		$exists = Customer::where('user_id', $user->id)
			->where('ssn', $request->ssn)
			->exists();

		if ($exists) {
			return response()->json([
				'status' => false,
				'errors' => __t('customer_with_same_SSN_already_exists_for_this_user', $language),
			], 422);
		}

		try {
			$nextUserCustomertId = Customer::where('user_id', $user->id)->max('user_customer_id') + 1;
			$customer = Customer::create([
				'user_id'   => $user->id,
				'company_id'   => $request->company_id,
				'user_customer_id' => $nextUserCustomertId,
				'ssn'       => $request->ssn,
				'full_name' => $request->full_name,
				'address'   => $request->address,
				'zip_code'  => $request->zip_code,
				'country'   => $request->country,
				'currency'  => $request->currency,
				'discount'  => $request->input('discount', $request->input('discound', 0)),
				'discound'  => $request->input('discound', $request->input('discount', 0)),
				'vat'       => $request->vat ?? 0,
				'telephone' => $request->telephone,
				'mobile'    => $request->mobile,
				'email'     => $request->email,
				'number_per_unit' => $request->number_per_unit,
				'invoice_in_email' => $request->invoice_in_email ?? 0,
				'invoice_in_online_bank' => $request->invoice_in_online_bank ?? 0,
				'send_electronic_invoice' => $request->input('send_electronic_invoice', $request->input('send_elecctronic_invoice', 0)),
				'send_elecctronic_invoice' => $request->input('send_elecctronic_invoice', $request->input('send_electronic_invoice', 0)),
				'payment_method' => $request->payment_method,
			]);

			return response()->json([
				'status' => true,
				'message' => __t('customeraddedsuccessfully', $language),
				'customer' => $customer,
			], 201);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'error' => $e->getMessage(),
			], 500);
		}
	}
	
	
	public function getCustomers(Request $request): JsonResponse 
	{
		$language = $request->header('Accept-Language', 'en');
    $user = auth()->user();
    $companyId = $request->input('company_id'); // selected company for employees
    $status = $request->input('status');
    
    $query = Customer::query();
    
    // CASE 1: Logged-in user is a COMPANY (company_id is empty)
    if ($companyId == '') {
        // Get employees linked to this company
        $employeeIds = $user->employees()->pluck('users.id')->toArray();
        
        // Include customers where:
        // 1️⃣ user_id = company's id (customers created by company)
        // 2️⃣ company_id = company's id (customers created by employees under this company)
        $query->where(function($q) use ($user) {
            $q->where('company_id', $user->id);
        });
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
        
        // Customers assigned to this employee by the company
        $assignedCustomerIds = [];
        if (!empty($pivot->customer_permissions)) {
            $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
        }
        
        // Fetch customers that belong to:
        // 1️⃣ Customers assigned to employee by company (via customer_permissions)
        // 2️⃣ Customers created by employee directly under this company context
        // 3️⃣ Customers created by the company itself
        $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {
            // Assigned customers
            if (!empty($assignedCustomerIds)) {
                $q->whereIn('id', $assignedCustomerIds);
            }
            
            // Customers created by employee under this company
            $q->orWhere(function ($sub) use ($user, $companyId) {
                $sub->where('user_id', $user->id)
                    ->where('company_id', $companyId);
            });
            
            // Customers created by the company itself
            $q->orWhere(function ($sub) use ($companyId) {
                $sub->where('user_id', $companyId)
                    ->where('company_id', $companyId);
            });
        });
    }
    
    // Optional filter by status
    if ($status != '') {
        $query->where('status', $status);
    }
    
    $customers = $query->with(['countryName', 'postalLocationName'])->get();
    
    return response()->json([
        'status' => true,
        'customers' => $customers,
    ]);
}

	
	
	public function ___getCustomers(Request $request): JsonResponse
	{
    $user = auth()->user();
    $status = $request->query('status');

    $query = Customer::query();

    // Check if user is a company
    $isCompany = $user->role === 'company';

    if ($isCompany) {
        // Get employees linked to this company
        $employeeIds = $user->employees()->pluck('users.id')->toArray();

        // Include self + employees
        $allUserIds = array_merge([$user->id], $employeeIds);

        $query->whereIn('user_id', $allUserIds);
    } else {
        // Employee: show only own customers
        $query->where('user_id', $user->id);
    }

    if ($status !== null) {
        $query->where('status', $status);
    }

    $customers = $query->with('countryName')->get();

    return response()->json([
        'status' => true,
        'customers' => $customers,
    ]);
}

	
	public function __getCustomers(Request $request): JsonResponse
	{
		$user = auth()->user();

		$status = $request->query('status'); // all, active, inactive

		$query = Customer::where('user_id', $user->id);
		//echo $status;exit;	
		if ($status === '1') {
			$query->where('status', '1');
		} elseif ($status === '0') {
			$query->where('status', '0');
		}		

		$customers = $query->with('countryName')->get();

		return response()->json([
			'status' => true,
			'customers' => $customers,
		]);
	}
	
	public function updateCustomer(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();

		$customer = Customer::where('user_id', $user->id)->where('id', $id)->first();
		
		if (!$customer) {
			$customer_employee_id = Customer::where('id', $id)->first();
			
//			echo $customer_employee_id->user_id."<br>";
//			echo $user->id;
//			exit;
			
			$companyEmployee = \DB::table('company_employees')
			->where('company_id', $user->id)
			->where('employee_id', $customer_employee_id->user_id)
			->first();
			
			if($companyEmployee){
					$customer = Customer::where('user_id', $customer_employee_id->user_id)->where('id', $id)->first();
			}else{
					return response()->json([
					'status' => false,
					'error' => __t('customer_not_found', $language),
				], 404);
			}
			
		}

//		if (!$customer) {
//			return response()->json([
//				'status' => false,
//				'error' => 'Customer not found',
//			], 404);
//		}

		$validator = Validator::make($request->all(), [
			'ssn'       => 'required|string',
			'full_name' => 'required|string|max:255',
			'email'     => 'nullable|email|max:100',
			'address'   => 'required|string',
			'zip_code'  => 'required|max:100',
			'country'   => 'required|max:100',
			'currency'  => 'required|string',
		], [
			// Custom messages (optional)
		], [
			// Attribute replacements
			'zip_code' => 'postal code',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}

		$customer->update($request->all());

		return response()->json([
			'status' => true,
			'message' => __t('customerupdatedsuccessfully', $language),
			'customer' => $customer,
		]);
	}

	
	public function deleteCustomer(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
		$user = auth()->user();

		$customer = Customer::where('user_id', $user->id)->where('id', $id)->first();

//		if (!$customer) {
//			return response()->json([
//				'status' => false,
//				'error' => 'Customer not found',
//			], 404);
//		}
		
		if (!$customer) {
			$customer_employee_id = Customer::where('id', $id)->first();
			
//			echo $customer_employee_id->user_id."<br>";
//			echo $user->id;
//			exit;
			
			$companyEmployee = \DB::table('company_employees')
			->where('company_id', $user->id)
			->where('employee_id', $customer_employee_id->user_id)
			->first();
			
			if($companyEmployee){
					$customer = Customer::where('user_id', $customer_employee_id->user_id)->where('id', $id)->first();
			}else{
					return response()->json([
					'status' => false,
					'error' => __t('customer_not_found', $language),
				], 404);
			}
			
		}

		$customer->delete();

		return response()->json([
			'status' => true,
			'message' => __t('customerdeletedsuccessfully', $language),
		]);
	}
	
	public function toggleCustomerStatus(Request $request, $id): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
		$user = auth()->user();

		$customer = Customer::where('user_id', $user->id)->where('id', $id)->first();

		
		
		
		
		
		if (!$customer) {
			$customer_employee_id = Customer::where('id', $id)->first();
			
//			echo $customer_employee_id->user_id."<br>";
//			echo $user->id;
//			exit;
			
			$companyEmployee = \DB::table('company_employees')
			->where('company_id', $user->id)
			->where('employee_id', $customer_employee_id->user_id)
			->first();
			
			if($companyEmployee){
					$customer = Customer::where('user_id', $customer_employee_id->user_id)->where('id', $id)->first();
			}else{
					return response()->json([
					'status' => false,
					'error' => __t('customer_not_found', $language),
				], 404);
			}
			
		}
		

		// Toggle status
		$customer->status = $customer->status == 1 ? 0 : 1;
		$customer->save();

		return response()->json([
			'status' => true,
			'message' => __t('statusupdatedsuccessfully', $language),
			'customer' => $customer,
		]);
	}
	
	public function getCustomerById(Request $request, $id): JsonResponse
	{
    $language = $request->header('Accept-Language', 'en'); 
		//echo $language;exit;
    $user = auth()->user();

    // Try to find customer owned by current user
    $customer = Customer::where('user_id', $user->id)->where('id', $id)->first();

    // If not found, check if customer belongs to an employee company relation
    if (!$customer) {
        $customer_employee = Customer::where('id', $id)->first();

        // If no such customer exists at all
        if (!$customer_employee) {
            return response()->json([
                'status' => false,
                'error' => __t('customer_not_found', $language),
            ], 404);
        }

        // Now check if current user is the company of that employee
        $companyEmployee = \DB::table('company_employees')
            ->where('company_id', $user->id)
            ->where('employee_id', $customer_employee->user_id)
            ->first();

        if ($companyEmployee) {
            $customer = Customer::where('user_id', $customer_employee->user_id)
                ->where('id', $id)
                ->first();
        } else {
            return response()->json([
                'status' => false,
                'error' => __t('customer_not_found', $language),
            ], 404);
        }
    }

    return response()->json([
        'status' => true,
        'customer' => $customer,
    ]);
}




}
