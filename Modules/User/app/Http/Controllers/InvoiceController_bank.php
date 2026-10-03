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
use Modules\User\app\Http\Services\LandsbankinnClaimsService;

class InvoiceController extends Controller
{
    protected $bankClaimsService;

    public function __construct(LandsbankinnClaimsService $bankClaimsService)
    {
        $this->bankClaimsService = $bankClaimsService;
    }

    /**
     * Create Invoice (Save as Draft or Send)
     */
    public function createInvoice(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
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
            'bank_claim_template_id' => 'nullable|string|required_if:send_claim_to_bank,true',
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
            'action' => 'required|in:save_draft,send',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first(),
            ], 422);
        }
        
        if ($request->send_in_email) {
            $customer = Customer::with('user')->find($request->customer_id);
            if (empty($customer->email)) {
                return response()->json([
                    'status' => false,
                    'errors' => 'Cannot send email: Customer does not have an email address',
                ], 422);
            }
        }

        // Validate bank claim requirements
        if ($request->send_claim_to_bank) {
            if (empty($request->bank_claim_template_id)) {
                return response()->json([
                    'status' => false,
                    'errors' => 'Bank claim template ID is required when sending claim to bank',
                ], 422);
            }
            
            $customer = Customer::find($request->customer_id);
            if (empty($customer->ssn)) {
                return response()->json([
                    'status' => false,
                    'errors' => 'Customer SSN is required for bank claim submission',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Get customer details
            $customer = Customer::find($request->customer_id);
            
            $companyId = $request->input('company_id');
            
            if ($request->action == 'save_draft') {
                $invoiceNo = null;
            } else {
                if ($companyId == '') {
                    $invoiceNo = Invoice::where('user_id', $user->id)->max('invoice_no') + 1;
                } else {
                    $invoiceNo = Invoice::where('user_id', $companyId)->max('invoice_no') + 1;
                }
            }

            // Create invoice
            $invoice = Invoice::create([
                'ssn' => $request->ssn,
                'user_id' => $user->id,
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
                'bank_claim_template_id' => $request->bank_claim_template_id,
                'currency' => $request->currency,
                'due_date' => $request->due_date,
                'deadline' => $request->deadline,
                'invoice_date' => $request->invoice_date,
                'status' => $request->action === 'send' ? 'sent' : 'draft',
                'created_by' => $user->company_name ?? $user->email,
            ]);

            // Calculate totals
            $subtotal = 0;
            $totalDiscount = 0;
            $totalVat = 0;

            // Create invoice items
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
            }

            // Apply global discount if provided
            if ($request->discount_percentage > 0) {
                $totalDiscount = $subtotal * ($request->discount_percentage / 100);
                $subtotal -= $totalDiscount;
            }

            // Update invoice totals
            $invoice->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_vat' => $totalVat,
                'total_amount' => $subtotal,
                'total_amount_incl_vat' => $subtotal + $totalVat,
            ]);

            // Submit claim to bank if requested and invoice is sent
            if ($request->action === 'send' && $request->send_claim_to_bank) {
                $this->submitClaimToBank($invoice);
            }

            // Send email if action is 'send' and send_in_email is true
            if ($request->action === 'send' && $request->send_in_email) {
                $this->sendInvoiceEmail($invoice);
            }

            DB::commit();

            $invoice->load('items', 'customer');

            $responseMessage = $request->action === 'send' ? 'Invoice created and sent successfully' : 'Invoice saved as draft';
            
            if ($request->send_claim_to_bank && $invoice->bank_claim_status === 'submitted') {
                $responseMessage .= '. Bank claim submitted successfully.';
            } elseif ($request->send_claim_to_bank && $invoice->bank_claim_status === 'failed') {
                $responseMessage .= '. Warning: Bank claim submission failed - ' . $invoice->bank_claim_error;
            }

            return response()->json([
                'status' => true,
                'message' => $responseMessage,
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit claim to Landsbankinn
     */
    private function submitClaimToBank($invoice)
    {
        try {
            $invoice->update([
                'bank_claim_status' => 'pending'
            ]);

            // Prepare additional claim data if needed
            $additionalData = [];
            
            // Submit claim to bank
            $result = $this->bankClaimsService->createClaimForInvoice(
                $invoice,
                $invoice->bank_claim_template_id,
                $additionalData
            );

            if ($result['success']) {
                $claimData = $result['data'];
                
                $invoice->update([
                    'bank_claim_id' => $claimData['id'] ?? null,
                    'bank_claim_status' => 'submitted',
                    'bank_claim_response' => json_encode($claimData),
                    'bank_claim_submitted_at' => now(),
                    'bank_claim_error' => null
                ]);

                return true;
            } else {
                $invoice->update([
                    'bank_claim_status' => 'failed',
                    'bank_claim_error' => $result['message'] ?? 'Unknown error',
                    'bank_claim_response' => json_encode($result)
                ]);

                return false;
            }
        } catch (\Exception $e) {
            $invoice->update([
                'bank_claim_status' => 'failed',
                'bank_claim_error' => $e->getMessage()
            ]);

            \Log::error('Bank claim submission failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no
            ]);

            return false;
        }
    }

    /**
     * Get Invoice List with Filters
     */
    public function getInvoices(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Invoice::with(['customer', 'items'])
            ->where('user_id', $user->id);

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('bank_claim_status') && $request->bank_claim_status !== 'all') {
            $query->where('bank_claim_status', $request->bank_claim_status);
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
    public function getInvoice($id): JsonResponse
    {
        $user = Auth::user();

        $invoice = Invoice::with(['customer', 'items.product', 'emailLogs'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found',
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
        $user = Auth::user();

        $invoice = Invoice::where('user_id', $user->id)->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // Can only update draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Only draft invoices can be updated',
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
            'bank_claim_template_id' => 'nullable|string|required_if:send_claim_to_bank,true',
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
            
            if ($request->action == 'save_draft') {
                $invoiceNo = null;
            } else {
                if ($companyId == '') {
                    $invoiceNo = Invoice::where('user_id', $user->id)->max('invoice_no') + 1;
                } else {
                    $invoiceNo = Invoice::where('user_id', $companyId)->max('invoice_no') + 1;
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
                'bank_claim_template_id' => $request->bank_claim_template_id,
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
                    'price_excl_vat' => $item['price_excl_vat'],
                    'price_incl_vat' => $item['price_incl_vat'],
                    'discount' => $item['discount'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);

                $invoiceItem->calculateTotals();
                $invoiceItem->save();

                $subtotal += $invoiceItem->line_total_excl_vat;
                $totalVat += ($invoiceItem->line_total_incl_vat - $invoiceItem->line_total_excl_vat);
            }

            if ($request->discount_percentage > 0) {
                $totalDiscount = $subtotal * ($request->discount_percentage / 100);
                $subtotal -= $totalDiscount;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total_vat' => $totalVat,
                'total_amount' => $subtotal,
                'total_amount_incl_vat' => $subtotal + $totalVat,
            ]);

            // Submit claim to bank if requested and invoice is being sent
            if ($request->action === 'send' && $request->send_claim_to_bank) {
                $this->submitClaimToBank($invoice);
            }

            if ($request->action === 'send' && $request->send_in_email) {
                $this->sendInvoiceEmail($invoice);
            }

            DB::commit();

            $invoice->load('items', 'customer');

            $responseMessage = $request->action === 'send' ? 'Invoice updated and sent successfully' : 'Invoice updated as draft';
            
            if ($request->send_claim_to_bank && $invoice->bank_claim_status === 'submitted') {
                $responseMessage .= '. Bank claim submitted successfully.';
            } elseif ($request->send_claim_to_bank && $invoice->bank_claim_status === 'failed') {
                $responseMessage .= '. Warning: Bank claim submission failed - ' . $invoice->bank_claim_error;
            }

            return response()->json([
                'status' => true,
                'message' => $responseMessage,
                'data' => $invoice,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update Payment Status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'payment_status' => 'required|in:paid,due,overdue'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the invoice
            $invoice = Invoice::findOrFail($id);

            // Check authorization
            if ($invoice->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $oldPaymentStatus = $invoice->payment_status;
            $newPaymentStatus = $request->payment_status;

            // Update payment status
            $invoice->payment_status = $newPaymentStatus;

            // If marking as paid, update the status to paid as well
            if ($newPaymentStatus === 'paid' && $invoice->status !== 'paid') {
                $invoice->status = 'paid';
                
                // Update bank claim status if exists
                if ($invoice->hasBankClaim() && $invoice->bank_claim_status !== 'paid') {
                    $invoice->bank_claim_status = 'paid';
                }
            }

            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'old_payment_status' => $oldPaymentStatus,
                    'new_payment_status' => $newPaymentStatus,
                    'status' => $invoice->status,
                    'bank_claim_status' => $invoice->bank_claim_status,
                    'total_amount_incl_vat' => $invoice->total_amount_incl_vat,
                    'updated_at' => $invoice->updated_at->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Invoice
     */
    public function deleteInvoice($id): JsonResponse
    {
        $user = Auth::user();

        $invoice = Invoice::where('user_id', $user->id)->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // Can only delete draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Only draft invoices can be deleted',
            ], 400);
        }

        $invoice->delete();

        return response()->json([
            'status' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Resend Invoice Email
     */
    public function resendInvoiceEmail($id): JsonResponse
    {
        $user = Auth::user();

        $invoice = Invoice::with(['items', 'customer'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$invoice) {
            return response()->json([
                'status' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        if ($invoice->status === 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Cannot send draft invoice. Please update status to sent first.',
            ], 400);
        }

        $this->sendInvoiceEmail($invoice);

        return response()->json([
            'status' => true,
            'message' => 'Invoice email sent successfully',
        ]);
    }

    /**
     * Get Bank Claim Templates
     */
    public function getBankClaimTemplates(Request $request): JsonResponse
    {
        try {
            $claimantNationalId = $request->get('claimant_national_id');
            $result = $this->bankClaimsService->getClaimTemplates($claimantNationalId);

            if ($result['success']) {
                return response()->json([
                    'status' => true,
                    'data' => $result['data']
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch bank claim templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Bank Claim Status for Invoice
     */
    public function getBankClaimStatus($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $invoice = Invoice::where('user_id', $user->id)->find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invoice not found',
                ], 404);
            }

            if (!$invoice->hasBankClaim()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No bank claim found for this invoice',
                ], 404);
            }

            // Fetch latest status from bank
            $result = $this->bankClaimsService->getClaim($invoice->bank_claim_id);

            if ($result['success']) {
                // Update local status
                $claimData = $result['data'];
                $invoice->update([
                    'bank_claim_status' => $claimData['status'] ?? $invoice->bank_claim_status,
                    'bank_claim_response' => json_encode($claimData)
                ]);

                return response()->json([
                    'status' => true,
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'bank_claim_id' => $invoice->bank_claim_id,
                        'bank_claim_status' => $invoice->bank_claim_status,
                        'bank_claim_details' => $claimData
                    ]
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch bank claim status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel Bank Claim
     */
    public function cancelBankClaim($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $invoice = Invoice::where('user_id', $user->id)->find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invoice not found',
                ], 404);
            }

            if (!$invoice->hasBankClaim()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No bank claim found for this invoice',
                ], 404);
            }

            if (!$invoice->isBankClaimActive()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bank claim is not active and cannot be cancelled',
                ], 400);
            }

            // Cancel claim at bank
            $result = $this->bankClaimsService->cancelClaim($invoice->bank_claim_id);

            if ($result['success']) {
                $invoice->update([
                    'bank_claim_status' => 'cancelled',
                    'bank_claim_response' => json_encode($result['data'])
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'Bank claim cancelled successfully',
                    'data' => $invoice
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to cancel bank claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry Bank Claim Submission
     */
    public function retryBankClaim($id): JsonResponse
    {
        try {
            $user = Auth::user();
            $invoice = Invoice::where('user_id', $user->id)->find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invoice not found',
                ], 404);
            }

            if ($invoice->bank_claim_status !== 'failed') {
                return response()->json([
                    'status' => false,
                    'message' => 'Can only retry failed bank claims',
                ], 400);
            }

            if (empty($invoice->bank_claim_template_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bank claim template ID is missing',
                ], 400);
            }

            // Retry submission
            $success = $this->submitClaimToBank($invoice);

            $invoice->refresh();

            if ($success) {
                return response()->json([
                    'status' => true,
                    'message' => 'Bank claim submitted successfully',
                    'data' => $invoice
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to submit bank claim: ' . $invoice->bank_claim_error,
                'data' => $invoice
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retry bank claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Invoice Email
     */
    private function sendInvoiceEmail($invoice)
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
                'error_message' => $emailSent ? null : 'Failed to send email',
            ]);

        } catch (\Exception $e) {
            InvoiceEmailLog::create([
                'invoice_id' => $invoice->id,
                'recipient_email' => $invoice->customer_email,
                'subject' => $subject ?? 'Invoice Email',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}