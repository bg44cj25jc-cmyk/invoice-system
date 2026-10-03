<?php

namespace Modules\User\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\app\Models\Product;
use Modules\User\app\Models\QuantityUnit;
use Modules\User\app\Models\Vat;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // 🟢 Helper to get all accessible user IDs
    private function getAccessibleUserIds($user, $companyId = null)
    {
        if (!empty($companyId)) {
            // employee → can see self + company
            return [$user->id, $companyId];
        }

        // company → can see self + employees
        $employeeIds = $user->employees()->pluck('users.id')->toArray();
        return array_merge([$user->id], $employeeIds);
    }

    // 🟢 Quantity units
    public function quantityUnits()
    {
        $units = QuantityUnit::where('status', 1)->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'units' => $units
        ]);
    }

    // 🟢 VATs
    public function vats()
    {
        $vats = Vat::where('status', 1)
            ->orderBy('percentage')
            ->get();

        return response()->json([
            'status' => true,
            'vats' => $vats
        ]);
    }

    // 🟢 Product list (index)
    public function productsList(Request $request)
	{
		$language = $request->header('Accept-Language', 'en'); 	
    $user = auth()->user();
    $companyId = $request->input('company_id'); // selected company for employees
    
    $query = Product::with('category');
    
    // CASE 1: Logged-in user is a COMPANY (company_id is empty)
    if ($companyId == '') {
        // Get employees linked to this company
        $employeeIds = $user->employees()->pluck('users.id')->toArray();
        
        // Include products where:
        // 1?? user_id = company's id (products created by company)
        // 2?? company_id = company's id (products created by employees under this company)
        $query->where(function($q) use ($user, $employeeIds) {
//            $q->where('user_id', $user->id)
//              ->orWhere('company_id', $user->id);
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
        
        // Find pivot record for this employee�company relationship
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
        
        // For employees, show products where:
        // 1?? user_id = company's id (products created by company)
        // 2?? company_id = company's id (products created by any employee under this company context)
        // This ensures employee sees all products in the company's catalog
        $query->where(function($q) use ($companyId) {
            $q->where('user_id', $companyId)
              ->orWhere('company_id', $companyId);
        });
    }
    
    // Apply status filter if provided
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }
    
    return response()->json([
        'status' => true,
        'products' => $query->get()
    ]);
}

    // 🟢 Create product
    public function store(Request $request)
    {
		$language = $request->header('Accept-Language', 'en'); 	
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'product_number' => 'required|string',
			'company_id' => 'nullable',
            'name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'price_excl_vat' => 'required|numeric|min:0',
			'price_incl_vat' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => __t('Validation error', $language),
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = Product::where('user_id', $user->id)
            ->where('product_number', $request->product_number)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => __t('Product number already exists for this user', $language)
            ], 422);
        }

        $price_incl_vat = $request->price_excl_vat * (1 + ($request->vat / 100));

//		if($request->company_id==""){
//			
//		}else{
//			
//		}
		
        $product = Product::create([
            'user_id' => $user->id,
			"company_id" => $request->company_id,
            'product_number' => $request->product_number,
            'name' => $request->name,
            'category_id' => $request->category_id,
            'unit_of_measure' => $request->unit_of_measure,
            'currency' => $request->currency ?? 'ISK',
            'vat' => $request->vat ?? '0',
            'price_excl_vat' => $request->price_excl_vat,
            'price_incl_vat' => $request->price_incl_vat,//$price_incl_vat,
            'cost_price' => $request->cost_price ?? 0,
            'allow_discount' => $request->allow_discount ?? 1,
            'max_discount' => $request->max_discount ?? 0,
            'number_per_unit' => $request->number_per_unit ?? 0,
            'note' => $request->note,
            'location' => $request->location,
            'supplier' => $request->supplier,
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => __t('product_created', $language),
            'product' => $product
        ], 201);
    }

    // 🟢 Show product
    public function show(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en'); 	
        $user = auth()->user();
        $companyId = $request->input('company_id');

        $accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

        $product = Product::with('category')
            ->whereIn('user_id', $accessibleUserIds)
            ->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => __t('product_not_found', $language)
            ], 404);
        }

        return response()->json([
            'status' => true,
            'product' => $product
        ]);
    }

    // 🟢 Update product
    public function update(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en'); 
        $user = auth()->user();
        $companyId = $request->input('company_id');
        $accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

        $product = Product::whereIn('user_id', $accessibleUserIds)->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => __t('product_not_found', $language)
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'price_excl_vat' => 'required|numeric|min:0',
			'price_incl_vat' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0|max:100',
            'unit_of_measure' => 'nullable|string',
            'currency' => 'nullable|string|max:10',
            'cost_price' => 'nullable|numeric|min:0',
            'allow_discount' => 'nullable|boolean',
            'max_discount' => 'nullable|numeric|min:0|max:100',
            'number_per_unit' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
            'location' => 'nullable|string',
            'supplier' => 'nullable|string',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => __t('validation_error', $language),
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

//        if (isset($data['price_excl_vat'], $data['vat'])) {
//            $data['price_incl_vat'] = $data['price_excl_vat'] * (1 + ($data['vat'] / 100));
//        }

        $product->update($data);

        return response()->json([
            'status' => true,
            'message' => __t('product_updated', $language),
            'product' => $product
        ]);
    }

    // 🟢 Delete product
    public function destroy(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en');
        $user = auth()->user();
        $companyId = $request->input('company_id');
        $accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

        $product = Product::whereIn('user_id', $accessibleUserIds)->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => __t('product_not_found', $language)
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => __t('productdeletedsuccessfully', $language)
        ]);
    }

    // 🟢 Toggle product status
    public function toggleStatus(Request $request, $id)
    {
		$language = $request->header('Accept-Language', 'en');
        $user = auth()->user();
        $companyId = $request->input('company_id');
        $accessibleUserIds = $this->getAccessibleUserIds($user, $companyId);

        $product = Product::whereIn('user_id', $accessibleUserIds)->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => __t('product_not_found', $language)
            ], 404);
        }

        $product->status = $product->status ? 0 : 1;
        $product->save();

        return response()->json([
            'status' => true,
            'message' => __t('statusupdatedsuccessfully', $language),
            'product' => $product
        ]);
    }
}
