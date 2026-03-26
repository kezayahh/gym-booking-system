<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'booking_id',
        'user_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================
    // AUTO GENERATE PAYMENT NUMBER
    // =========================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = 'PAY-' . strtoupper(Str::random(8));
            }
        });
    }

    // =========================
    // RELATIONSHIPS
    // =========================
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }

    // =========================
    // SCOPES
    // =========================
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', ['refunded', 'partially_refunded']);
    }

    // =========================
    // BUSINESS METHODS
    // =========================

    public function markAsCompleted($transactionId = null)
    {
        $this->status = 'completed';
        $this->transaction_id = $transactionId;
        $this->paid_at = now();
        return $this->save();
    }

    public function markAsFailed()
    {
        $this->status = 'failed';
        return $this->save();
    }

    public function markAsRefunded()
    {
        $this->status = 'refunded';
        return $this->save();
    }

    // =========================
    // ACCESSORS (VERY IMPORTANT FOR VUE)
    // =========================

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('M d, Y h:i A')
            : null;
    }

    public function getCreatedAtDateAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('Y-m-d')
            : null;
    }

    public function getCreatedAtRawAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('Y-m-d')
            : null;
    }
}