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
use Modules\User\app\Models\UserDeleteReason;
use Modules\User\app\Models\Category;	
use Modules\User\app\Models\Product;
use Illuminate\Support\Facades\DB;
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

class CategoryController extends Controller
{
	
	private function getAccessibleUserIds($user, $companyId = null)
	{
		if (!empty($companyId)) {
			return [$user->id, $companyId];
		}

		// If company login, include employees
		$employeeIds = $user->employees()->pluck('users.id')->toArray();

		// Company + its employees
		return array_merge([$user->id], $employeeIds);
	}

	
	public function categoryList(Request $request): JsonResponse
	{
		$language = $request->header('Accept-Language', 'en'); 
    $user = auth()->user();
    $companyId = $request->input('company_id'); // selected company for employees
    $status = $request->input('status'); // optional filter
    
    $query = Category::query();
    
    // CASE 1: Logged-in user is a COMPANY (company_id is empty)
    if ($companyId == '') {
        // Include categories where:
        // 1️⃣ user_id = company's id (categories created by company)
        // 2️⃣ company_id = company's id (categories created by employees under this company)
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
        
        // For employees, show categories where:
        // 1️⃣ Categories created by employee under this company context
        // 2️⃣ Categories created by the company itself
        // 3️⃣ Categories created by other employees under this company (shared resources)
        $query->where(function ($q) use ($user, $companyId) {
            // Categories created by employee under this company
            $q->where(function ($sub) use ($user, $companyId) {
                $sub->where('user_id', $user->id)
                    ->where('company_id', $companyId);
            });
            
            // Categories created by the company itself
            $q->orWhere(function ($sub) use ($companyId) {
                $sub->where('user_id', $companyId)
                    ->where('company_id', $companyId);
            });
            
            // Categories created by other employees under this company
            $q->orWhere('company_id', $companyId);
        });
    }
    
    // Optional status filter
    if ($status != '') {
        $query->where('status', $status);
    }
    
    // Load categories with products
    $categories = $query->with('products')->get();
    
    return response()->json([
        'status' => true,
        'categories' => $categories,
    ]);
}

	
    public function __index()
    {
        $user = auth()->user();
        $categories = Category::where('user_id', $user->id)->get();

        return response()->json(['status' => true, 'categories' => $categories]);
    }

    public function store(Request $request)
	{
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();

		$validator = Validator::make($request->all(), [
			'name' => [
				'required',
				'string',
				Rule::unique('categories')->where(function ($query) use ($user) {
					return $query->where('user_id', $user->id);
				}),
			],
			'discount' => 'nullable|numeric|min:0|max:100',
			'company_id' => 'nullable',
		]);
		
		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => __t('validation_error', $language),
				'errors' => $validator->errors()
			], 422);
		}

		$category = Category::create([
			'user_id' => $user->id,
			'name' => $request->name,
			'company_id' => $request->company_id,
			'discount' => $request->discount ?? 0,
			'status' => 1,
		]);

		return response()->json([
			'status' => true,
			'message' => __t('category_created', $language),
			'category' => $category,
		], 201);
	}

    public function update(Request $request, $id)
	{
		$language = $request->header('Accept-Language', 'en');
    $user = auth()->user();
    $companyId = $request->input('company_id');

    $accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

    $category = Category::whereIn('user_id', $accessibleUserIds)->findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => [
            'required',
            'string',
            Rule::unique('categories')->where(function ($query) use ($category) {
                return $query->where('user_id', $category->user_id);
            })->ignore($category->id),
        ],
        'discount' => 'nullable|numeric|min:0|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => __t('validation_error', $language),
            'errors' => $validator->errors()
        ], 422);
    }

    $category->update($request->only(['name', 'discount', 'status']));

    return response()->json([
        'status' => true,
        'message' => __t('categoryupdatedsuccessfully', $language),
        'category' => $category,
    ]);
}

	
	public function show(Request $request, $id)
	{
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();
		$companyId = $request->input('company_id');

		$accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

		$category = Category::whereIn('user_id', $accessibleUserIds)->find($id);

		if (!$category) {
			return response()->json([
				'status' => false,
				'message' => __t('category_not_found', $language)
			], 404);
		}

		return response()->json([
			'status' => true,
			'category' => $category
		]);
}

	public function active(Request $request)
	{
		$user = auth()->user();
		$companyId = $request->input('company_id');

		$accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

		$categories = Category::whereIn('user_id', $accessibleUserIds)
			->where('status', 1)
			->orderBy('name')
			->get();

		return response()->json([
			'status' => true,
			'categories' => $categories
		]);
	}


    public function destroy(Request $request, $id)
	{
		$language = $request->header('Accept-Language', 'en'); 	
		$user = auth()->user();
		$companyId = $request->input('company_id');

		$accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

		$category = Category::whereIn('user_id', $accessibleUserIds)->findOrFail($id);

		$hasProducts = Product::whereIn('user_id', $accessibleUserIds)
			->where('category_id', $category->id)
			->exists();

		if ($hasProducts) {
			return response()->json([
				'status' => false,
				'message' => __t('category_cannot_be_deleted_because_products_are_assigned_to_it', $language)
			], 400);
		}

		$category->delete();

		return response()->json([
			'status' => true,
			'message' => __t('categorydeletedsuccessfully', $language)
		]);
	}


    public function toggleStatus(Request $request, $id)
	{
		$language = $request->header('Accept-Language', 'en'); 	
		$user = auth()->user();
		$companyId = $request->input('company_id');

		$accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

		$category = Category::whereIn('user_id', $accessibleUserIds)->find($id);

		if (!$category) {
			return response()->json([
				'status' => false,
				'message' => __t('category_not_found', $language)
			], 404);
		}

		$category->status = $category->status ? 0 : 1;
		$category->save();

		return response()->json([
			'status' => true,
			'message' => __t('statusupdatedsuccessfully', $language),
			'category' => $category
		]);
}


}
