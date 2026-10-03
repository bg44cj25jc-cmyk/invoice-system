<?php

namespace Modules\User\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\User\App\Http\Services\UserService;
use Modules\User\App\Http\Requests\UserRequest;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Modules\Home\App\Models\Specilaties;
use Illuminate\Support\Facades\DB;

use Modules\User\App\Models\TempUser;
use Modules\Login\App\Models\User;
use Modules\DashBoard\App\Models\FrontEndUser;
use Modules\DashBoard\App\Models\Payment;
use Modules\DashBoard\App\Models\Invoice;
use Modules\DashBoard\App\Models\Product;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

	
	public function companyList(Request $request): JsonResponse
	{
    $search = $request->input('search');

    $companies = FrontEndUser::with('country_details')
        ->where('role', 'company')
        ->when($search, function ($q) use ($search) {
            $q->where('company_name', 'LIKE', "%$search%")
              ->orWhere('email', 'LIKE', "%$search%");
        })
        ->select('id', 'company_name', 'email', 'phone_number', 'country', 'city',
                'active_plan_name', 'plan_expiry_date', 'status')
        ->orderBy('id', 'DESC')
        ->paginate(10);

    return response()->json([
        'status'  => true,
        'message' => 'Company list fetched successfully',
        'list'    => $companies,
        'data'    => $companies->items()
    ]);
}





	
	public function companyDetails($id): JsonResponse
	{
		$company = FrontEndUser::where('id', $id)
			->where('role', 'company')
			->first();

		if (!$company) {
			return response()->json([
				'status' => false,
				'message' => 'Company not found'
			]);
		}

		return response()->json([
			'status' => true,
			'message' => 'Company details fetched',
			'data' => $company
		]);
	}
 
	
	public function companyListPage()
	{
    	return view('user::user_list');
	}
	
	public function companyDetailsPage($id)
	{ //echo 111;exit;
		$company = FrontEndUser::with(['plan', 'postalCodeDetail'])->find($id);
		

		if (!$company) {
			abort(404, 'Company not found');
		}

//		// Fetch employees via pivot table
//		$company->get();
//		echo $company->country_detils->name;
		$employees = $company->employees()->get();
		
		$products = Product::where('company_id', $id)->get();
	//echo "<pre>";print_r($company); exit;

		return view('user::company_details', compact('company', 'employees','products'));
	}

	public function updateCompanyStatus(Request $request)
	{
    $company = FrontEndUser::find($request->id);

    if (!$company) {
        return response()->json(['status' => false, 'message' => 'Company not found']);
    }

    $company->status = $company->status == 1 ? 0 : 1; // toggle
    $company->save();

    return response()->json([
        'status' => true,
        'message' => 'Status updated successfully',
        'newStatus' => $company->status
    ]);
}

	public function employeeList(Request $request)
{
    // Only companies that actually have employees
    $companies = FrontEndUser::where('role', 'company')
        ->whereIn('id', function($query){
            $query->select('company_id')->from('company_employees');
        })
        ->orderBy('company_name','ASC')
        ->get();

    return view('user::employee-list', compact('companies'));
}

	
	public function employeeListJson(Request $request): JsonResponse
{
    $filterCompanyId = $request->company_id;
    $filterName = $request->employee_name;

    $employees = FrontEndUser::query()
        ->select(
            'users.id',
            'users.contact_person_name as full_name',
            'users.email',
            'users.phone_number',
            'users.status',
            'company.company_name as company_name'
        )
        ->join('company_employees', 'company_employees.employee_id', '=', 'users.id')
        ->leftJoin('users as company', 'company.id', '=', 'company_employees.company_id')
        ->where('users.role', 'employee');

    if (!empty($filterCompanyId)) {
        $employees->where('company.id', $filterCompanyId);
    }

    if (!empty($filterName)) {
        $employees->where('users.contact_person_name', 'like', "%".$filterName."%");
    }

    $employees = $employees->orderBy('users.id', 'DESC')->paginate(10);

    return response()->json([
        'status' => true,
        'message' => 'Employees fetched successfully',
        'list' => $employees,
        'data' => $employees->items()
    ]);
}


	public function viewEmployee($id)
	{
		$employee = FrontEndUser::with('companies')->findOrFail($id);

		return view('user::employee-view', compact('employee'));
	}

	
	
	

	public function paymentList(Request $request)
	{
    $query = Payment::with(['user.companies'])->orderBy('id', 'desc');

    // FILTER BY COMPANY
    if ($request->user_id) {
        $query->where('user_id', $request->user_id);
    }

    // DATE FILTER
    if ($request->from_date && $request->to_date) {
        $query->whereBetween('created_at', [
            \Carbon\Carbon::createFromFormat('d-m-Y', $request->from_date)->startOfDay(),
            \Carbon\Carbon::createFromFormat('d-m-Y', $request->to_date)->endOfDay()
        ]);
    }

    // UNIQUE USERS WHO MADE PAYMENT
    $users = Payment::with('user')
        ->select('user_id')
        ->groupBy('user_id')
        ->get()
        ->map(function ($item) {
            return $item->user;
        });

    $payments = $query->paginate(20);

    return view('user::payment-history', compact('payments', 'users'));
}


public function paymentDetails($id)
{
    $payment = Payment::with(['user', 'companies', 'customer', 'invoice'])->findOrFail($id);
	//echo "<pre>";print_r($payment);exit;
	$mainUrl = env('MAIN_URL');
    return view('user::payment-details', compact('payment', 'mainUrl'));
}


 



public function invoiceList(Request $request)
{
    $query = Invoice::with(['user','customer','items','company']);

    // Filter by selected company
    if ($request->user_id) {
        $query->where('company_id', $request->user_id);
    }

    // Date Filter
    if ($request->from_date && $request->to_date) {
        $query->whereBetween('created_at', [
            Carbon::createFromFormat('d-m-Y', $request->from_date)->startOfDay(),
            Carbon::createFromFormat('d-m-Y', $request->to_date)->endOfDay()
        ]);
    }

    $invoices = $query->orderBy('id', 'desc')->paginate(20);

    // Only load companies who have invoices
    $users = FrontEndUser::where('role', 'company')
        ->whereHas('invoices')   // <-- IMPORTANT
        ->get();

    return view('user::invoice-history', compact('invoices','users'));
}




	public function invoiceDetails($id)
{
    $invoice = Invoice::with(['user', 'items'])->findOrFail($id);
		//echo env('MAIN_URL'); exit;
		$mainUrl = env('MAIN_URL');
		//echo "<pre>";print_r($invoice);exit;
    return view('user::invoice-details', compact('invoice', 'mainUrl'));
}







    /**
     * Create User View
     */
    public function create()
    {
        $user = auth()->user();
        if (empty($user)) {
            return redirect('/login');
        }

        $speciality = $this->userService->fetchSpeciality();
		$cities = DB::table('cities')->get();
		$standard_message = DB::table('welcomes')
            ->get()->toArray();
        return view('user::create', ['speciality' => $speciality, 'cities' => $cities, 'standard_message' => $standard_message]);
    }

	public function edit($id)
	{ 	//echo 111;exit;
		$user = auth()->user();
		if (empty($user)) {
			return redirect('/login');
		}

		$user = DB::table('tempusers')->where('id', $id)->first();
		$speciality = $this->userService->fetchSpeciality();
		$cities = DB::table('cities')->get();
		//echo $id;

		//echo "<pre>";print_r($user);exit;
		$municipalities = DB::table('municipal')->where('city_id', $user->city)->get();
		$standard_message = DB::table('welcomes')
			->whereIn('id', [3,4,5])
			->get()->toArray();
		return view('user::edit', ['user'=>$user, 'speciality' => $speciality, 'cities' => $cities, 'municipalities' => $municipalities,
									'standard_message' => $standard_message]);
	}

	public function getMunicipalities($cityId)
	{
		$municipalities = DB::table('municipal')->where('city_id', $cityId)->get();
		return response()->json($municipalities);
	}


    

    


}
