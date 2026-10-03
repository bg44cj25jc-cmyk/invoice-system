<?php
namespace Modules\User\app\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// ==========================================
// Invoice Model
// ==========================================
class Invoice extends Model
{
    use SoftDeletes;

    // Payment Status Constants
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_DUE = 'due';
    const PAYMENT_STATUS_OVERDUE = 'overdue';

    // Invoice Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ssn',
        'user_id',
		'company_id',
        'customer_id',
        'invoice_no',
        'customer_name',
        'customer_email',
        'customer_address',
        'regarding_job',
        'order_number',
        'comment_notes',
        'reference_request_number',
        'discount_percentage',
        'payment_mode',
        'send_in_email',
        'send_electronic_invoice',
        'send_claim_to_bank',
        'currency',
        'due_date',
        'deadline',
        'invoice_date',
        'subtotal',
        'total_discount',
        'total_vat',
        'total_amount',
        'total_amount_incl_vat',
        'status',
        'created_by',
        'payment_status',
        'bank_claim_id',
        'bank_claim_template_id',
        'bank_claim_status',
        'bank_claim_response',
        'bank_claim_submitted_at',
        'bank_claim_error',
    ];

    protected $casts = [
        'send_in_email' => 'boolean',
        'send_electronic_invoice' => 'boolean',
        'send_claim_to_bank' => 'boolean',
        'discount_percentage' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_incl_vat' => 'decimal:2',
        'due_date' => 'date',
        'deadline' => 'date',
        'invoice_date' => 'date',
    ];

    protected $dates = ['deleted_at'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(InvoiceEmailLog::class);
    }

    // Generate unique invoice number
    public static function generateInvoiceNumber($userId)
    {
        $lastInvoice = self::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_no, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        return 'INV-' . date('Y') . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    // Get available payment statuses
    public static function getPaymentStatuses()
    {
        return [
            self::PAYMENT_STATUS_PAID => 'Paid',
            self::PAYMENT_STATUS_DUE => 'Due',
            self::PAYMENT_STATUS_OVERDUE => 'Overdue',
        ];
    }

    // Get available invoice statuses
    public static function getInvoiceStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SENT => 'Sent',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    // Check if invoice is paid
    public function isPaid()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    // Check if invoice is overdue
    public function isOverdue()
    {
        return $this->payment_status === self::PAYMENT_STATUS_OVERDUE;
    }

    // Check if invoice is due
    public function isDue()
    {
        return $this->payment_status === self::PAYMENT_STATUS_DUE;
    }

    // Update payment status
    public function updatePaymentStatus($newStatus)
    {
        $validStatuses = array_keys(self::getPaymentStatuses());
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid payment status: {$newStatus}");
        }

        $this->payment_status = $newStatus;

        // Auto-update invoice status when marking as paid
        if ($newStatus === self::PAYMENT_STATUS_PAID && $this->status !== self::STATUS_PAID) {
            $this->status = self::STATUS_PAID;
        }

        return $this->save();
    }

    // Get payment status label
    public function getPaymentStatusLabelAttribute()
    {
        $statuses = self::getPaymentStatuses();
        return $statuses[$this->payment_status] ?? 'Unknown';
    }

    // Get invoice status label
    public function getInvoiceStatusLabelAttribute()
    {
        $statuses = self::getInvoiceStatuses();
        return $statuses[$this->status] ?? 'Unknown';
    }

    // Scope for filtering by status
    public function scopeStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    // Scope for filtering by payment status
    public function scopePaymentStatus($query, $paymentStatus)
    {
        if ($paymentStatus && $paymentStatus !== 'all') {
            return $query->where('payment_status', $paymentStatus);
        }
        return $query;
    }

    // Scope for filtering by date range
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        if ($fromDate) {
            $query->whereDate('invoice_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('invoice_date', '<=', $toDate);
        }
        return $query;
    }

    // Scope for search
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('order_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    // Scope for overdue invoices
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_OVERDUE);
    }

    // Scope for paid invoices
    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    // Scope for due invoices
    public function scopeDue($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_DUE);
    }
}