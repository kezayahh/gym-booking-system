<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_number',
        'booking_id',
        'payment_id',
        'user_id',
        'refund_amount',
        'original_amount',
        'reason',
        'status',
        'admin_notes',
        'processed_by',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($refund) {
            if (empty($refund->refund_number)) {
                $refund->refund_number = 'REF-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeProcessed($query)
    {
        return $query->whereIn('status', ['processed', 'completed']);
    }

    // Methods
    public function approve($adminId, $notes = null)
    {
        return $this->update([
            'status' => 'approved',
            'admin_notes' => $notes,
            'processed_by' => $adminId,
            'processed_at' => now(),
        ]);
    }

    public function reject($adminId, $notes)
    {
        return $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'processed_by' => $adminId,
            'processed_at' => now(),
        ]);
    }

    public function markAsProcessed()
    {
        return $this->update(['status' => 'processed']);
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
        $this->payment->markAsRefunded();
        
        return true;
    }
}