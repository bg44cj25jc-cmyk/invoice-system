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
use Modules\User\app\Models\Product;
use Modules\User\app\Models\QuantityUnit;
use Modules\User\app\Models\Vat;
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

use Modules\Clerk\Http\Controllers\SectionController;

use Modules\User\app\Http\Services\PayPalService;




class ProductController extends Controller
{
	
	public function quantityUnits()
    {
        $units = QuantityUnit::where('status', 1)->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'units' => $units
        ]);
    }
	
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

	
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Product::with('category')->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['status' => true, 'products' => $query->get()]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'product_number' => 'required|string',
            'name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'price_excl_vat' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0|max:100',
        ]);
		
		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}
		
		// Check if product already exists
		$exists = Product::where('user_id', $user->id)
			->where('product_number', $request->product_number)
			->exists();

		if ($exists) {
			return response()->json([
				'status' => false,
				'message' => 'Product number already exists for this user'
			], 422);
		}

        $price_incl_vat = $request->price_excl_vat * (1 + ($request->vat / 100));

        $product = Product::create([
            'user_id' => $user->id,
            'product_number' => $request->product_number,
            'name' => $request->name,
            'category_id' => $request->category_id,
            'unit_of_measure' => $request->unit_of_measure,
            'currency' => $request->currency ?? 'ISK',
            'vat' => $request->vat ?? '0',
            'price_excl_vat' => $request->price_excl_vat,
            'price_incl_vat' => $price_incl_vat,
            'cost_price' => $request->cost_price ?? 0,
            'allow_discount' => $request->allow_discount ?? 1,
            'max_discount' => $request->max_discount ?? 0,
            'number_per_unit' => $request->number_per_unit ?? 0,
            'note' => $request->note,
            'location' => $request->location,
            'supplier' => $request->supplier,
            'status' => 1,
        ]);

        return response()->json(['status' => true, 'message' => 'Product created', 'product' => $product], 201);
    }

    public function show($id)
    {
        $user = auth()->user();
        $product = Product::with('category')->where('user_id', $user->id)->find($id);
		if (!$product) {
			return response()->json([
				'status' => false,
				'message' => 'Product not found'
			], 404);
		}
        return response()->json(['status' => true, 'product' => $product]);
    }

    public function update(Request $request, $id)
	{
		$user = auth()->user();
		$product = Product::where('user_id', $user->id)->find($id);

		if (!$product) {
			return response()->json([
				'status' => false,
				'message' => 'Product not found'
			], 404);
		}

		$validator = Validator::make($request->all(), [
			'name' => 'required|string',
			'category_id' => 'nullable|exists:categories,id',
			'price_excl_vat' => 'required|numeric|min:0',
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
				'message' => 'Validation error',
				'errors' => $validator->errors()
			], 422);
		}

		$data = $validator->validated();

		// Auto calculate price_incl_vat
		if (isset($data['price_excl_vat'], $data['vat'])) {
			$data['price_incl_vat'] = $data['price_excl_vat'] * (1 + ($data['vat'] / 100));
		}

		$product->update($data);

		return response()->json([
			'status' => true,
			'message' => 'Product updated',
			'product' => $product
		]);
	}


    public function destroy($id)
    {
        $user = auth()->user();
         $product = Product::where('user_id', $user->id)->find($id);

		if (!$product) {
			return response()->json([
				'status' => false,
				'message' => 'Product not found'
			], 404);
		}

        $product->delete();

        return response()->json(['status' => true, 'message' => 'Product deleted']);
    }

    public function toggleStatus($id)
    {
        $user = auth()->user();
        $product = Product::where('user_id', $user->id)->find($id);

		if (!$product) {
			return response()->json([
				'status' => false,
				'message' => 'Product not found'
			], 404);
		}

        $product->status = $product->status ? 0 : 1;
        $product->save();

        return response()->json(['status' => true, 'message' => 'Status updated', 'product' => $product]);
    }
}
