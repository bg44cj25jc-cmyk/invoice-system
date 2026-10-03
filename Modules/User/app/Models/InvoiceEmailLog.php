<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


// ==========================================
// InvoiceEmailLog Model
// ==========================================
class InvoiceEmailLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'recipient_email',
        'subject',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scope for successful emails
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'sent');
    }

    // Scope for failed emails
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Check if email was sent successfully
    public function wasSuccessful()
    {
        return $this->status === 'sent';
    }
}