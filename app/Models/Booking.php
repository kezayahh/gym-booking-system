<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'user_id',
        'schedule_id',
        'number_of_slots',
        'total_amount',
        'status',
        'booking_date',
        'special_requests',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Boot method for auto-generating booking number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = 'BK-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereHas('schedule', function($q) {
            $q->where('date', '>=', now());
        });
    }

    // Accessors
    public function getIsPaidAttribute()
    {
        return $this->payment && $this->payment->status === 'completed';
    }

    public function getCanCancelAttribute()
    {
        return in_array($this->status, ['pending', 'confirmed']) && 
               $this->schedule->date > now()->addHours(24);
    }

    public function getCanRefundAttribute()
    {
        return $this->status === 'cancelled' && 
               $this->isPaid && 
               !$this->refund;
    }

    // Methods
    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // Free up the slots
        $this->schedule->decrementBookedSlots($this->number_of_slots);

        return true;
    }

    public function confirm()
    {
        return $this->update(['status' => 'confirmed']);
    }

    public function complete()
    {
        return $this->update(['status' => 'completed']);
    }
}