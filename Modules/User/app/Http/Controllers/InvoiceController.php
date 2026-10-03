<?php

namespace Modules\User\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Modules\User\app\Models\Invoice;
use Modules\User\app\Models\InvoiceItem;
use Modules\User\app\Models\InvoiceEmailLog;
use Modules\User\app\Models\Customer;
use Modules\User\app\Models\Product;
use Carbon\Carbon;
use Modules\User\app\Models\User;

class InvoiceController extends Controller
{ 
    
	
	public function getDashboardSummary(Request $request): JsonResponse
{
    $user = Auth::user();
    $companyId = $request->input('company_id');
    $filter = $request->input('filter', 'last_12_months'); 

    switch ($filter) {
        case 'last_6_months':
            $startDate = now()->subMonths(5)->startOfMonth();
            $months = 6;
            break;

        case 'this_year':
            $startDate = now()->startOfYear();
            $months = now()->month;
            break;

        default:
            $startDate = now()->subMonths(11)->startOfMonth();
            $months = 12;
            break;
    }

    $endDate = now()->endOfMonth();

    $baseQuery = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when(!$companyId, fn($q) => $q->where('user_id', $user->id));

    // --- FIXED SUMMARY LOGIC ---
    // --- FIXED SUMMARY LOGIC ---
$summary = (clone $baseQuery)
    ->selectRaw('
        SUM(total_amount_incl_vat) as total_amount,

        SUM(CASE WHEN payment_status = "paid" THEN total_amount_incl_vat ELSE 0 END) as paid,

        SUM(
            CASE 
                WHEN payment_status = "paid" THEN 0
                ELSE total_amount_incl_vat
            END
        ) as unpaid,

        SUM(CASE WHEN payment_status = "overdue" THEN total_amount_incl_vat ELSE 0 END) as overdue
    ')
    ->first();


    // --- FIXED MONTHLY CHART LOGIC ---
    $monthlyData = (clone $baseQuery)
    ->selectRaw('
        DATE_FORMAT(invoice_date, "%b") as month_name,

        SUM(CASE WHEN payment_status = "paid" THEN total_amount_incl_vat ELSE 0 END) as paid,

        SUM(
            CASE 
                WHEN payment_status = "paid" THEN 0
                ELSE total_amount_incl_vat
            END
        ) as unpaid
    ')
    ->groupByRaw('YEAR(invoice_date), MONTH(invoice_date), DATE_FORMAT(invoice_date, "%b")')
    ->orderByRaw('YEAR(invoice_date), MONTH(invoice_date)')
    ->get();

    // --- Fill missing months ---
    $monthsList = collect();
    $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate->copy()->addMonth());

    foreach ($period as $month) {
        $monthName = $month->format('M');
        $monthData = $monthlyData->firstWhere('month_name', $monthName);

        $monthsList->push([
            'month' => $monthName,
            'paid' => (float) ($monthData->paid ?? 0),
            'unpaid' => (float) ($monthData->unpaid ?? 0),
        ]);
    }

    return response()->json([
        'status' => true,
        'filter' => $filter,
        'date_range' => [
            'start' => $startDate->toDateString(),
            'end'   => $endDate->toDateString(),
        ],
        'months' => $months,
		'data' => [
            'total_amount' => number_format($summary->total_amount ?? 0, 2, '.', ''),
            'paid'         => number_format($summary->paid ?? 0, 2, '.', ''),
            'unpaid'       => number_format($summary->unpaid ?? 0, 2, '.', ''),
            'overdue'      => number_format($summary->overdue ?? 0, 2, '.', ''),
        ],
        'chart' => $monthsList,
    ]);
}
	
	public function __getDashboardSummary(Request $request): JsonResponse
{
    $user = Auth::user();
    $companyId = $request->input('company_id');
    $filter = $request->input('filter', 'last_12_months'); // Default: last 12 months

    // --- Determine date range ---
    switch ($filter) {
        case 'last_6_months':
            $startDate = now()->subMonths(5)->startOfMonth();
            $months = 6;
            break;

        case 'this_year':
            $startDate = now()->startOfYear();
            $months = now()->month;
            break;

        default: // last_12_months
            $startDate = now()->subMonths(11)->startOfMonth();
            $months = 12;
            break;
    }

    $endDate = now()->endOfMonth();

    // --- Base Query ---
    $baseQuery = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when(!$companyId, fn($q) => $q->where('user_id', $user->id));

    // --- Totals by status (top summary box) ---
    $summary = (clone $baseQuery)
        ->selectRaw('
            SUM(total_amount_incl_vat) as total_amount,
            SUM(CASE WHEN payment_status = "paid" THEN total_amount_incl_vat ELSE 0 END) as paid,
            SUM(
                CASE 
                    WHEN (payment_status = "due" OR (status = "sent" AND payment_status != "paid")) 
                    THEN total_amount_incl_vat 
                    ELSE 0 
                END
            ) as unpaid,
            SUM(CASE WHEN payment_status = "overdue" THEN total_amount_incl_vat ELSE 0 END) as overdue
        ')
        ->first();

    // --- Monthly breakdown (chart box) ---
    $monthlyData = (clone $baseQuery)
        ->selectRaw('
            DATE_FORMAT(invoice_date, "%b") as month_name,
            SUM(CASE WHEN payment_status = "paid" THEN total_amount_incl_vat ELSE 0 END) as paid,
            SUM(
                CASE 
                    WHEN (payment_status = "due" OR (status = "sent" AND payment_status != "paid")) 
                    THEN total_amount_incl_vat 
                    ELSE 0 
                END
            ) as unpaid
        ')
        ->groupByRaw('YEAR(invoice_date), MONTH(invoice_date), DATE_FORMAT(invoice_date, "%b")')
        ->orderByRaw('YEAR(invoice_date), MONTH(invoice_date)')
        ->get();

    // --- Fill missing months (for smooth chart) ---
    $monthsList = collect();
    $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate->copy()->addMonth());
    foreach ($period as $month) {
        $monthName = $month->format('M');
        $monthData = $monthlyData->firstWhere('month_name', $monthName);
        $monthsList->push([
            'month' => $monthName,
            'paid' => (float) ($monthData->paid ?? 0),
            'unpaid' => (float) ($monthData->unpaid ?? 0),
        ]);
    }

    // --- Return clean formatted response ---
    return response()->json([
        'status' => true,
        'filter' => $filter,
        'date_range' => [
            'start' => $startDate->toDateString(),
            'end'   => $endDate->toDateString(),
        ],
        'months' => $months,
        'data' => [
            'total_amount' => number_format($summary->total_amount ?? 0, 2, '.', ''),
            'paid'         => number_format($summary->paid ?? 0, 2, '.', ''),
            'unpaid'       => number_format($summary->unpaid ?? 0, 2, '.', ''),
            'overdue'      => number_format($summary->overdue ?? 0, 2, '.', ''),
        ],
        'chart' => $monthsList,
    ]);
}









	public function getTopProducts(Request $request): JsonResponse
	{
    $user = Auth::user();
    $companyId = $request->input('company_id');
    $currentYear = now()->year;

    // --- Query function ---
    $getData = function ($from = null, $to = null) use ($companyId, $user) {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select(
                'invoice_items.product_name',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.line_total_incl_vat) as total_sales')
            )
            ->when($from && $to, fn($q) => $q->whereBetween('invoices.invoice_date', [$from, $to]))
            ->when(!$to && $from, fn($q) => $q->where('invoices.invoice_date', '<', $from)) // for "previous" years
            ->when($companyId, fn($q) => $q->where('invoices.company_id', $companyId))
            ->when(!$companyId, fn($q) => $q->where('invoices.user_id', $user->id))
			->where('invoices.payment_status', 'paid') 
            ->groupBy('invoice_items.product_name')
            ->get();
    };

    // --- Current year (this year only) ---
    $currentYearData = $getData(
        now()->startOfYear(),
        now()->endOfYear()
    );

    // --- Previous years (everything before this year) ---
    $previousYearsData = $getData(
        now()->startOfYear(), // use this as upper bound
        null
    );

    // --- Merge unique product names ---
    $allProducts = collect($previousYearsData)->pluck('product_name')
        ->merge(collect($currentYearData)->pluck('product_name'))
        ->unique()
        ->values();

    // --- Combine both datasets ---
    $chartData = $allProducts->map(function ($name) use ($previousYearsData, $currentYearData) {
        $prev = $previousYearsData->firstWhere('product_name', $name);
        $curr = $currentYearData->firstWhere('product_name', $name);

        return [
            'product_name'   => $name,
            'previous_year'  => (float) ($prev->total_sales ?? 0),
            'current_year'   => (float) ($curr->total_sales ?? 0),
        ];
    })
    ->sortByDesc(fn($row) => $row['current_year'] + $row['previous_year'])
    ->take(6)
    ->values();

    return response()->json([
        'status' => true,
        'year' => $currentYear,
        'data' => $chartData,
    ]);
}




	public function getSalesByYear(Request $request): JsonResponse
{
    $user = Auth::user();
    $companyId = $request->input('company_id');
    $year = $request->input('year', now()->year);

    $monthlySales = DB::table('invoices')
        ->select(
            DB::raw('MONTH(invoice_date) as month'),
            DB::raw('SUM(total_amount_incl_vat) as total_sales')
        )
        ->whereYear('invoice_date', $year)
        ->where('payment_status', 'paid') // ✅ Only include paid invoices
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when(!$companyId, fn($q) => $q->where('user_id', $user->id))
        ->groupBy(DB::raw('MONTH(invoice_date)'))
        ->orderBy('month')
        ->get();

    // --- Make sure all 12 months are represented ---
    $data = collect(range(1, 12))->map(function ($month) use ($monthlySales) {
        $found = $monthlySales->firstWhere('month', $month);
        return [
            'month' => $month,
            'total_sales' => (float) ($found->total_sales ?? 0),
        ];
    });

    return response()->json([
        'status' => true,
        'year' => $year,
        'data' => $data,
    ]);
}


	
	
	
	
	/**
     * Create Invoice (Save as Draft or Send)
     */
    public function createInvoice(Request $request): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en');
        $user = Auth::user();

				$rules = [
			'action' => 'required|in:save_draft,send',
		];

		// Base rules that always apply
		if ($request->action === 'save_draft') {
			// Only customer_id required for drafts
			$rules['company_id'] = 'nullable';
			$rules['customer_id'] = 'required|exists:customers,id';
			
			// Everything else optional / nullable
			$rules['regarding_job'] = 'nullable|string|max:255';
			$rules['order_number'] = 'nullable|string|max:255';
			$rules['comment_notes'] = 'nullable|string';
			$rules['reference_request_number'] = 'nullable|string|max:255';
			$rules['discount_percentage'] = 'nullable|numeric|min:0|max:100';
			$rules['payment_mode'] = 'nullable|string|max:100';
			$rules['send_in_email'] = 'nullable|boolean';
			$rules['send_electronic_invoice'] = 'nullable|boolean';
			$rules['send_claim_to_bank'] = 'nullable|boolean';
			$rules['currency'] = 'nullable|string|max:10';
			$rules['due_date'] = 'nullable|date';
			$rules['deadline'] = 'nullable|date';
			$rules['invoice_date'] = 'nullable|date';
			$rules['items'] = 'nullable|array';
			$rules['items.*.product_id'] = 'nullable|exists:products,id';
			$rules['items.*.product_number'] = 'nullable|string|max:100';
			$rules['items.*.product_name'] = 'nullable|string|max:255';
			$rules['items.*.quantity'] = 'nullable|numeric|min:0.01';
			$rules['items.*.price_excl_vat'] = 'nullable|numeric|min:0';
			$rules['items.*.price_incl_vat'] = 'nullable|numeric|min:0';
			$rules['items.*.discount'] = 'nullable|numeric|min:0|max:100';
			$rules['items.*.notes'] = 'nullable|string';
		} else {
			// Full validation when sending
			$rules = array_merge($rules, [
				'company_id' => 'nullable',
				'customer_id' => 'required|exists:customers,id',
				'regarding_job' => 'nullable|string|max:255',
				'order_number' => 'nullable|string|max:255',
				'comment_notes' => 'nullable|string',
				'reference_request_number' => 'nullable|string|max:255',
				'discount_percentage' => 'nullable|numeric|min:0|max:100',
				'payment_mode' => 'nullable|string|max:100',
				'send_in_email' => 'nullable|boolean',
				'send_electronic_invoice' => 'nullable|boolean',
				'send_claim_to_bank' => 'nullable|boolean',
				'currency' => 'required|string|max:10',
				'due_date' => 'nullable|date',
				'deadline' => 'nullable|date',
				'invoice_date' => 'required|date',
				'items' => 'required|array|min:1',
				'items.*.product_id' => 'nullable|exists:products,id',
				'items.*.product_number' => 'nullable|string|max:100',
				'items.*.product_name' => 'required|string|max:255',
				'items.*.quantity' => 'required|numeric|min:0.01',
				'items.*.price_excl_vat' => 'required|numeric|min:0',
				'items.*.price_incl_vat' => 'required|numeric|min:0',
				'items.*.discount' => 'nullable|numeric|min:0|max:100',
				'items.*.notes' => 'nullable|string',
			]);
		}

		$validator = Validator::make($request->all(), $rules);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()->first(),
			], 422);
		}
		
		if ($request->send_in_email) {
			$customer = Customer::with('user')->find($request->customer_id);
			//echo "<pre>";print_r($customer);exit;
			if (empty($customer->email)) {
				return response()->json([
					'status' => false,
					'errors' => __t('email_missing_for_customer', $language),
				], 422);
			}
		}

        DB::beginTransaction();

        try {
            // Get customer details
            $customer = Customer::find($request->customer_id);

            // Generate invoice number
            //$invoiceNo = Invoice::generateInvoiceNumber($user->id);
			
			$companyId = $request->input('company_id');
			
			if($request->action=='save_draft'){
				
					$invoiceNo =  null;
				
			}else{
				if ($companyId=='') {
					$invoiceNo =  Invoice::where('user_id', $user->id)->max('invoice_no') + 1;
					
					$users = User::find($user->id);
					$companyId = $user->id;
				}else{
					$invoiceNo =  Invoice::where('user_id', $companyId)->max('invoice_no') + 1;
					
					$users = User::find($companyId);
					
				}
				
				
				$now = Carbon::now();
				$expiryDate = Carbon::parse($users->plan_expiry_date);
				if ($expiryDate->lt($now)) {
					DB::rollBack();
					return response()->json([
						'status' => false,
						'message' => __t('your_plan_has_expired_please_renew_to_continue', $language),
					], 403);
				}
				
				if($users->is_cancel_plan == 'yes'){
					DB::rollBack();
					return response()->json([
						'status' => false,
						//'message' => __t('you_have_reached_your_invoice_limit', $language),
						'error' =>  __t('plan_canceled_cannot_create_invoice', $language),
					], 500);
				}
				if($users->invoice_count > 0){
						$users->invoice_count = ($users->invoice_count - 1);
						$users->save();
				}else{
					DB::rollBack();
					return response()->json([
						'status' => false,
						//'message' => __t('you_have_reached_your_invoice_limit', $language),
						'error' =>  __t('you_have_reached_your_invoice_limit', $language),
					], 500);
				}
					
				
			}
				

            // Create invoice
            $invoice = Invoice::create([
                'ssn' => $request->ssn,
                'user_id' => $user->id,
				'company_id' => $companyId,
                'customer_id' => $request->customer_id,
                'invoice_no' => $invoiceNo,
                'customer_name' => $customer->customer_name ?? $customer->full_name,
                'customer_email' => $customer->email,
                'customer_address' => $customer->address,
                'regarding_job' => $request->regarding_job,
                'order_number' => $request->order_number,
                'comment_notes' => $request->comment_notes,
                'reference_request_number' => $request->reference_request_number,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'payment_mode' => $request->payment_mode,
                'send_in_email' => $request->send_in_email ?? false,
                'send_electronic_invoice' => $request->send_electronic_invoice ?? false,
                'send_claim_to_bank' => $request->send_claim_to_bank ?? false,
                'currency' => $request->currency,
                'due_date' => $request->due_date,
                'deadline' => $request->deadline,
                'invoice_date' => $request->invoice_date,
                'status' => $request->action === 'send' ? 'sent' : 'draft',
                //'created_by' => $user->company_name ?? $user->email,
				'created_by' => $user->contact_person_name ?? $user->email,
            ]);

            // Calculate totals
            $subtotal = 0;
            $totalDiscount = 0;
            $totalVat = 0;

            // Create invoice items
			//$request->action === 'send' && 
            if (!empty($request->items)) {
				foreach ($request->items as $item) {
					$invoiceItem = new InvoiceItem([
						'invoice_id' => $invoice->id,
						'product_id' => $item['product_id'] ?? null,
						'product_number' => $item['product_number'] ?? null,
						'product_name' => $item['product_name'],
						'quantity' => $item['quantity'],
						'price_excl_vat' => $item['price_excl_vat'],
						'price_incl_vat' => $item['price_incl_vat'],
						'discount' => $item['discount'] ?? 0,
						'notes' => $item['notes'] ?? null,
					]);

					$invoiceItem->calculateTotals();
					$invoiceItem->save();

					$subtotal += $invoiceItem->line_total_excl_vat;
					$totalVat += ($invoiceItem->line_total_incl_vat - $invoiceItem->line_total_excl_vat);
					$totalDiscount += (float) $item['price_excl_vat']
						* (float) $item['quantity']
						* ((float) ($item['discount'] ?? 0) / 100);
				}

				// Apply global discount if provided
				$globalDiscountRate = (float) ($request->discount_percentage ?? 0) / 100;
				if ($globalDiscountRate > 0) {
					$globalDiscount = $subtotal * $globalDiscountRate;
					$totalDiscount += $globalDiscount;
					$subtotal -= $globalDiscount;
					$totalVat *= 1 - $globalDiscountRate;
				}
			}

            // Update invoice totals
            $invoice->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_vat' => $totalVat,
                'total_amount' => $subtotal,
                'total_amount_incl_vat' => $subtotal + $totalVat,
            ]);

            // Send email if action is 'send' and send_in_email is true
            if ($request->action === 'send' && $request->send_in_email) {
                $this->sendInvoiceEmail($invoice, $language);
            }

            DB::commit();

            $invoice->load('items', 'customer');

            return response()->json([
                'status' => true,
                'message' => $request->action === 'send'
											? __t('invoice_created_and_sent_successfully', $language)
											: __t('invoice_saved_as_draft', $language),
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => __t('failed_to_create_invoice', $language),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Invoice List with Filters
     */
	
	
	
	public function getInvoicesList(Request $request): JsonResponse
{
    $language = $request->header('Accept-Language', 'en');
    $user = Auth::user();
    $companyId = $request->input('company_id');

    // Base query
    $query = Invoice::with(['customer', 'items']);

    /**
     * CASE 1: USER IS COMPANY (company_id empty)
     */
    if ($companyId == '') {

        // Invoice belongs to company OR invoice created by a company employee
        $query->where('company_id', $user->id);
    }

    /**
     * CASE 2: USER IS EMPLOYEE (company_id provided)
     */
    else {

        // Must have companyId
        if (!$companyId) {
            return response()->json([
                'status' => false,
                'message' => __t('Company ID is required for employees', $language),
            ], 400);
        }

        // Check if employee belongs to company
        $pivot = \DB::table('company_employees')
            ->where('company_id', $companyId)
            ->where('employee_id', $user->id)
            ->first();

        if (!$pivot) {
            return response()->json([
                'status' => false,
                'message' => __t('you_are_not_associated_with_this_company', $language),
            ], 403);
        }

        // Customers assigned to employee
        $assignedCustomerIds = [];
        if (!empty($pivot->customer_permissions)) {
            $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
        }

        // Apply employee filter logic
        $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {

            // 1️⃣ Invoices for assigned customers
            if (!empty($assignedCustomerIds)) {
                $q->whereIn('customer_id', $assignedCustomerIds);
            }

            // 2️⃣ Invoices created by employee for this company
            $q->orWhere(function ($sub) use ($user, $companyId) {
                $sub->where('user_id', $user->id)
                    ->where('company_id', $companyId);
            });

            // 3️⃣ Invoices created by company itself
            $q->orWhere(function ($sub) use ($companyId) {
                $sub->where('user_id', $companyId)
                    ->where('company_id', $companyId);
            });
        });
    }

    /**
     * APPLY FILTERS
     */
    if ($request->has('status') && $request->status !== 'all') {
        $query->where('status', $request->status);
    }

    if ($request->has('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    if ($request->has('from_date')) {
        $query->whereDate('invoice_date', '>=', $request->from_date);
    }

    if ($request->has('to_date')) {
        $query->whereDate('invoice_date', '<=', $request->to_date);
    }

    if ($request->has('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('invoice_no', 'LIKE', "%{$search}%")
                ->orWhere('customer_name', 'LIKE', "%{$search}%")
                ->orWhere('order_number', 'LIKE', "%{$search}%");
        });
    }

    /**
     * SORT + PAGINATE
     */
    $sortBy = $request->get('sort_by', 'created_at');
    $sortOrder = $request->get('sort_order', 'desc');

    $query->orderBy($sortBy, $sortOrder);

    $perPage = $request->get('per_page', 15);
    $invoices = $query->paginate($perPage);

    return response()->json([
        'status' => true,
        'data' => $invoices,
    ]);
}

	
	
	
	
    public function _18_10_2025_getInvoices(Request $request): JsonResponse
    {
        $user = Auth::user();
		//echo $user->id;exit;
        $query = Invoice::with(['customer', 'items'])
            ->where('user_id', $user->id);
		
		
//		$query = Invoice::with(['customer', 'items'])
//            ->where('user_id', $user->id)->get();
//echo "<pre>"; print_r($query);exit;


        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('order_number', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $invoices = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Get Single Invoice Details
     */
    public function getInvoice(Request $request): JsonResponse
{
    $language = $request->header('Accept-Language', 'en');
    $user = Auth::user();
    $companyId = $request->input('company_id'); // company selected by employee
    $id = $request->input('invoice_id');
    // Base query
    $query = Invoice::with(['customer', 'items.product', 'emailLogs'])
        ->where('id', $id);

    /**
     * CASE 1: Logged-in user is a COMPANY (company_id empty)
     */
    if ($companyId == '') {
        $query->where('company_id', $user->id); // invoices created by company or employees under this company
    }
    /**
     * CASE 2: Logged-in user is an EMPLOYEE (company_id provided)
     */
    else {

        if (!$companyId) {
            return response()->json([
                'status' => false,
                'message' => __t('Company ID is required for employees', $language)
            ], 400);
        }

        // Check if employee is linked to company
        $pivot = \DB::table('company_employees')
            ->where('company_id', $companyId)
            ->where('employee_id', $user->id)
            ->first();

        if (!$pivot) {
            return response()->json([
                'status' => false,
                'message' => __t('you_are_not_associated_with_this_company', $language),
            ], 403);
        }

        // Customer permissions
        $assignedCustomerIds = [];
        if (!empty($pivot->customer_permissions)) {
            $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
        }

        // Apply SAME logic as list
        $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {

            // 1️⃣ Invoices of assigned customers
            if (!empty($assignedCustomerIds)) {
                $q->whereIn('customer_id', $assignedCustomerIds);
            }

            // 2️⃣ Invoices created by the employee under the selected company
            $q->orWhere(function ($sub) use ($user, $companyId) {
                $sub->where('user_id', $user->id)
                    ->where('company_id', $companyId);
            });

            // 3️⃣ Invoices created by the company itself
            $q->orWhere(function ($sub) use ($companyId) {
                $sub->where('user_id', $companyId)
                    ->where('company_id', $companyId);
            });
        });
    }

    // Fetch final invoice
    $invoice = $query->first();

    if (!$invoice) {
        return response()->json([
            'status' => false,
            'message' => __t('invoice_not_found', $language),
        ], 404);
    }

    return response()->json([
        'status' => true,
        'data' => $invoice,
    ]);
}


    /**
     * Update Invoice
     */
    public function updateInvoice(Request $request, $id): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en'); 
        $user = Auth::user();

        $companyId = $request->input('company_id');

// Base query to fetch invoice with full relationships
$query = Invoice::with(['customer', 'items'])->where('id', $id);

/**
 * CASE 1: Logged-in user is a COMPANY (company_id empty)
 */
if ($companyId == '') {
    $query->where('company_id', $user->id);
}

/**
 * CASE 2: Logged-in user is an EMPLOYEE (company_id provided)
 */
else {

    if (!$companyId) {
        return response()->json([
            'status' => false,
            'message' => __t('Company ID is required for employees', $language),
        ], 400);
    }

    // Check employee-company link
    $pivot = \DB::table('company_employees')
        ->where('company_id', $companyId)
        ->where('employee_id', $user->id)
        ->first();

    if (!$pivot) {
        return response()->json([
            'status' => false,
            'message' => __t('you_are_not_associated_with_this_company', $language),
        ], 403);
    }

    // Customer permissions
    $assignedCustomerIds = [];
    if (!empty($pivot->customer_permissions)) {
        $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
    }

    // Apply same access rules as getInvoicesList()
    $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {

        // 1️⃣ Invoices assigned to employee customers
        if (!empty($assignedCustomerIds)) {
            $q->whereIn('customer_id', $assignedCustomerIds);
        }

        // 2️⃣ Invoices created by employee under this company
        $q->orWhere(function ($sub) use ($user, $companyId) {
            $sub->where('user_id', $user->id)
                ->where('company_id', $companyId);
        });

        // 3️⃣ Invoices created by the company
        $q->orWhere(function ($sub) use ($companyId) {
            $sub->where('user_id', $companyId)
                ->where('company_id', $companyId);
        });
    });
}

$invoice = $query->first();

if (!$invoice) {
    return response()->json([
        'status' => false,
        'message' => __t('invoice_not_found', $language),
    ], 404);
}

        // Can only update draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => __t('only_draft_invoices_can_be_updated', $language),
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'ssn' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'regarding_job' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:255',
            'comment_notes' => 'nullable|string',
            'reference_request_number' => 'nullable|string|max:255',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_mode' => 'nullable|string|max:100',
            'send_in_email' => 'nullable|boolean',
            'send_electronic_invoice' => 'nullable|boolean',
            'send_claim_to_bank' => 'nullable|boolean',
            'currency' => 'required|string|max:10',
            'due_date' => 'nullable|date',
            'deadline' => 'nullable|date',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_number' => 'nullable|string|max:100',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price_excl_vat' => 'required|numeric|min:0',
			'items.*.price_incl_vat' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            //items.*.vat_percentage' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'action' => 'required|in:save_draft,send',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Get customer details
            $customer = Customer::find($request->customer_id);
			
			
			$companyId = $request->input('company_id');
			
			if($request->action=='save_draft'){
				
					$invoiceNo =  null;
				
			}else{
				
				if ($companyId=='') {
					$invoiceNo =  Invoice::where('user_id', $user->id)->max('invoice_no') + 1;
					
					$users = User::find($user->id);
				}else{
					$invoiceNo =  Invoice::where('user_id', $companyId)->max('invoice_no') + 1;
					
					$users = User::find($companyId);
					
				}
				
				$now = Carbon::now();
				$expiryDate = Carbon::parse($users->plan_expiry_date);
				if ($expiryDate->lt($now)) {
					DB::rollBack();
					return response()->json([
						'status' => false,
						'message' => __t('your_plan_has_expired_please_renew_to_continue', $language),
					], 403);
				}
				
				if($users->is_cancel_plan == 'yes'){
					DB::rollBack();
					return response()->json([
						'status' => false,
						//'message' => __t('you_have_reached_your_invoice_limit', $language),
						'error' =>  __t('plan_canceled_cannot_create_invoice', $language),
					], 500);
				}
				if($users->invoice_count > 0){
						$users->invoice_count = ($users->invoice_count - 1);
						$users->save();
				}else{
					DB::rollBack();
					return response()->json([
						'status' => false,
						//'message' => __t('you_have_reached_your_invoice_limit', $language),
						'error' =>  __t('you_have_reached_your_invoice_limit', $language),
					], 500);
				}
			}
				

           
			

            // Update invoice
            $invoice->update([
				'invoice_no' => $invoiceNo,
                'ssn' => $request->ssn,
                'customer_id' => $request->customer_id,
                'customer_name' => $customer->customer_name ?? $customer->full_name,
                'customer_email' => $customer->email,
                'customer_address' => $customer->address,
                'regarding_job' => $request->regarding_job,
                'order_number' => $request->order_number,
                'comment_notes' => $request->comment_notes,
                'reference_request_number' => $request->reference_request_number,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'payment_mode' => $request->payment_mode,
                'send_in_email' => $request->send_in_email ?? false,
                'send_electronic_invoice' => $request->send_electronic_invoice ?? false,
                'send_claim_to_bank' => $request->send_claim_to_bank ?? false,
                'currency' => $request->currency,
                'due_date' => $request->due_date,
                'deadline' => $request->deadline,
                'invoice_date' => $request->invoice_date,
                'status' => $request->action === 'send' ? 'sent' : 'draft',
            ]);

            // Delete old items and create new ones
            $invoice->items()->delete();

            $subtotal = 0;
            $totalDiscount = 0;
            $totalVat = 0;

            foreach ($request->items as $item) {
                $invoiceItem = new InvoiceItem([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_number' => $item['product_number'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    //'unit_of_measure' => $item['unit_of_measure'] ?? null,
                    'price_excl_vat' => $item['price_excl_vat'],
					'price_incl_vat' => $item['price_incl_vat'],
                    'discount' => $item['discount'] ?? 0,
                    //'vat_percentage' => $item['vat_percentage'],
                    'notes' => $item['notes'] ?? null,
                ]);

                $invoiceItem->calculateTotals();
                $invoiceItem->save();

                $subtotal += $invoiceItem->line_total_excl_vat;
                $totalVat += ($invoiceItem->line_total_incl_vat - $invoiceItem->line_total_excl_vat);
                $totalDiscount += (float) $item['price_excl_vat']
                    * (float) $item['quantity']
                    * ((float) ($item['discount'] ?? 0) / 100);
            }

            $globalDiscountRate = (float) ($request->discount_percentage ?? 0) / 100;
            if ($globalDiscountRate > 0) {
                $globalDiscount = $subtotal * $globalDiscountRate;
                $totalDiscount += $globalDiscount;
                $subtotal -= $globalDiscount;
                $totalVat *= 1 - $globalDiscountRate;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_vat' => $totalVat,
                'total_amount' => $subtotal,
                'total_amount_incl_vat' => $subtotal + $totalVat,
            ]);

            if ($request->action === 'send' && $request->send_in_email) {
                $this->sendInvoiceEmail($invoice, $language);
            }

            DB::commit();

            $invoice->load('items', 'customer');

            return response()->json([
                'status' => true,
                'message' => $request->action === 'send' ? __t('invoice_updated_and_sent_successfully', $language) : __t('invoice_updated_as_draft', $language),
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => __t('failed_to_update_invoice', $language),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
	
	public function updatePaymentStatus(Request $request, $id)
	{
    $language = $request->header('Accept-Language', 'en');

    try {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:paid,due,overdue'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __t('validation_error', $language),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $companyId = $request->input('company_id');

        // Base query for invoice
        $query = Invoice::where('id', $id);

        /**
         * CASE 1: USER IS COMPANY (company_id empty)
         */
        if ($companyId == '') {
            $query->where('company_id', $user->id);
        }
        /**
         * CASE 2: USER IS EMPLOYEE (company_id provided)
         */
        else {
            // Company ID must be provided
            if (!$companyId) {
                return response()->json([
                    'success' => false,
                    'message' => __t('Company ID is required for employees', $language),
                ], 400);
            }

            // Check pivot relationship
            $pivot = \DB::table('company_employees')
                ->where('company_id', $companyId)
                ->where('employee_id', $user->id)
                ->first();

            if (!$pivot) {
                return response()->json([
                    'success' => false,
                    'message' => __t('you_are_not_associated_with_this_company', $language),
                ], 403);
            }

            // Get assigned customer IDs
            $assignedCustomerIds = [];
            if (!empty($pivot->customer_permissions)) {
                $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
            }

            // Apply employee access rules
            $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {

                // Invoices assigned to employee customers
                if (!empty($assignedCustomerIds)) {
                    $q->whereIn('customer_id', $assignedCustomerIds);
                }

                // Invoices created by employee under this company
                $q->orWhere(function ($sub) use ($user, $companyId) {
                    $sub->where('user_id', $user->id)
                        ->where('company_id', $companyId);
                });

                // Invoices created by the company
                $q->orWhere(function ($sub) use ($companyId) {
                    $sub->where('user_id', $companyId)
                        ->where('company_id', $companyId);
                });
            });
        }

        // Fetch invoice
        $invoice = $query->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => __t('invoice_not_found', $language),
            ], 404);
        }

        // Store old status
        $oldPaymentStatus = $invoice->payment_status;
        $newPaymentStatus = $request->payment_status;

        // Update payment status
        $invoice->payment_status = $newPaymentStatus;

        // If marking as paid, set status = paid
        if ($newPaymentStatus === 'paid' && $invoice->status !== 'paid') {
            $invoice->status = 'paid';
        }

        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => __t('payment_status_updated_successfully', $language),
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'old_payment_status' => $oldPaymentStatus,
                'new_payment_status' => $newPaymentStatus,
                'status' => $invoice->status,
                'total_amount_incl_vat' => $invoice->total_amount_incl_vat,
                'updated_at' => $invoice->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => __t('invoice_not_found', $language),
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __t('failed_to_update_payment_status', $language),
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Delete Invoice
     */
    public function deleteInvoice(Request $request): JsonResponse
{
    $language = $request->header('Accept-Language', 'en');
    $user = Auth::user();
    $companyId = $request->input('company_id');
	$id = $request->input('invoice_id');
    // Base query
    $query = Invoice::where('id', $id);

    /**
     * CASE 1: USER IS COMPANY (company_id empty)
     */
    if ($companyId == '') {
        $query->where('company_id', $user->id);
    }

    /**
     * CASE 2: USER IS EMPLOYEE (company_id provided)
     */
    else {

        // Validate employee-company association
        $pivot = \DB::table('company_employees')
            ->where('company_id', $companyId)
            ->where('employee_id', $user->id)
            ->first();

        if (!$pivot) {
            return response()->json([
                'status' => false,
                'message' => __t('you_are_not_associated_with_this_company', $language),
            ], 403);
        }

        // Assigned customers for employee
        $assignedCustomerIds = [];
        if (!empty($pivot->customer_permissions)) {
            $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
        }

        // Apply employee access rules
        $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {

            // Invoices assigned to the employee
            if (!empty($assignedCustomerIds)) {
                $q->whereIn('customer_id', $assignedCustomerIds);
            }

            // Invoices created by employee under this company
            $q->orWhere(function ($sub) use ($user, $companyId) {
                $sub->where('user_id', $user->id)
                    ->where('company_id', $companyId);
            });

            // Invoices created by company
            $q->orWhere(function ($sub) use ($companyId) {
                $sub->where('user_id', $companyId)
                    ->where('company_id', $companyId);
            });
        });
    }

    // Fetch invoice
    $invoice = $query->first();

    if (!$invoice) {
        return response()->json([
            'status' => false,
            'message' => __t('invoice_not_found', $language),
        ], 404);
    }

    // Can only delete draft invoices
    if ($invoice->status !== 'draft') {
        return response()->json([
            'status' => false,
            'message' => __t('only_draft_can_be_deleted', $language),
        ], 400);
    }

    // Delete
    $invoice->delete();

    return response()->json([
        'status' => true,
        'message' => __t('invoice_deleted_successfully', $language),
    ]);
}


    /**
     * Resend Invoice Email
     */
    public function resendInvoiceEmail(Request $request, $id): JsonResponse
	{
    $language = $request->header('Accept-Language', 'en');
    $user = Auth::user();
    $companyId = $request->input('company_id');

    // Base query with relationships
    $query = Invoice::with(['items', 'customer'])->where('id', $id);

    /**
     * CASE 1: USER IS COMPANY (company_id empty)
     */
    if ($companyId == '') {
        $query->where('company_id', $user->id);
    }

    /**
     * CASE 2: USER IS EMPLOYEE (company_id provided)
     */
    else {

        // Check pivot relationship
        $pivot = \DB::table('company_employees')
            ->where('company_id', $companyId)
            ->where('employee_id', $user->id)
            ->first();

        if (!$pivot) {
            return response()->json([
                'status' => false,
                'message' => __t('you_are_not_associated_with_this_company', $language),
            ], 403);
        }

        // Assigned customers
        $assignedCustomerIds = [];
        if (!empty($pivot->customer_permissions)) {
            $assignedCustomerIds = json_decode($pivot->customer_permissions, true);
        }

        // Apply employee access rules
        $query->where(function ($q) use ($assignedCustomerIds, $user, $companyId) {

            // Invoices assigned to employee
            if (!empty($assignedCustomerIds)) {
                $q->whereIn('customer_id', $assignedCustomerIds);
            }

            // Invoices created by employee under this company
            $q->orWhere(function ($sub) use ($user, $companyId) {
                $sub->where('user_id', $user->id)
                    ->where('company_id', $companyId);
            });

            // Invoices created by the company
            $q->orWhere(function ($sub) use ($companyId) {
                $sub->where('user_id', $companyId)
                    ->where('company_id', $companyId);
            });
        });
    }

    // Fetch invoice after applying all rules
    $invoice = $query->first();

    if (!$invoice) {
        return response()->json([
            'status' => false,
            'message' => __t('invoice_not_found', $language),
        ], 404);
    }

    if ($invoice->status === 'draft') {
        return response()->json([
            'status' => false,
            'message' => __t('cannot_send_draft_invoice', $language),
        ], 400);
    }

    // Final send
    $this->sendInvoiceEmail($invoice, $language);

    return response()->json([
        'status' => true,
        'message' => __t('invoice_email_sent_successfully', $language),
    ]);
}


    /**
     * Send Invoice Email
     */
    private function sendInvoiceEmail($invoice, $language)
    {
        try {
            $to = $invoice->customer_email;
            $subject = "Invoice #{$invoice->invoice_no} from {$invoice->created_by}";
            $site_url = env('WEB_URL');
			$profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $invoice->user->profile_logo;
            $message = view('email.invoice', [
                'invoice' => $invoice,
                'site_url' => $site_url,
				'profile_logo' => $profile_logo
            ])->render();

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";

            $emailSent = mail($to, $subject, $message, $headers);

            // Log email
            InvoiceEmailLog::create([
                'invoice_id' => $invoice->id,
                'recipient_email' => $to,
                'subject' => $subject,
                'status' => $emailSent ? 'sent' : 'failed',
                'error_message' => $emailSent ? null : __t('failed_to_send_email', $language),
            ]);

        } catch (\Exception $e) {
            InvoiceEmailLog::create([
                'invoice_id' => $invoice->id,
                'recipient_email' => $invoice->customer_email,
                'subject' => $subject ?? __t('invoice_email', $language),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}