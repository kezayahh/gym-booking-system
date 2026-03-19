<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'total_capacity',
        'booked_slots',
        'price_per_slot',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'price_per_slot' => 'decimal:2',
    ];

    // Relationships
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
                    ->where('booked_slots', '<', $this->total_capacity);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', Carbon::today());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', Carbon::today());
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', Carbon::today());
    }
public function getDurationHoursAttribute()
    {
        $start = Carbon::parse($this->start_time);
        $end   = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end) / 60; // 7–9 = 2 hours
    }

    public function getTotalPriceAttribute()
    {
        return $this->duration_hours * $this->price_per_slot; // price_per_hour
    }
    // Accessors
    public function getAvailableSlotsAttribute()
    {
        return $this->total_capacity - $this->booked_slots;
    }

    public function getIsFullAttribute()
    {
        return $this->booked_slots >= $this->total_capacity;
    }

    public function getTimeSlotAttribute()
    {
        return Carbon::parse($this->start_time)->format('h:i A') . ' - ' . 
               Carbon::parse($this->end_time)->format('h:i A');
    }

    // Methods
    public function canBook($numberOfSlots = 1)
    {
        return $this->status === 'available' && 
               ($this->booked_slots + $numberOfSlots) <= $this->total_capacity &&
               $this->date >= Carbon::today();
    }

    public function incrementBookedSlots($numberOfSlots = 1)
    {
        $this->increment('booked_slots', $numberOfSlots);
        
        if ($this->booked_slots >= $this->total_capacity) {
            $this->update(['status' => 'full']);
        }
    }

    public function decrementBookedSlots($numberOfSlots = 1)
    {
        $this->decrement('booked_slots', $numberOfSlots);
        
        if ($this->status === 'full' && $this->booked_slots < $this->total_capacity) {
            $this->update(['status' => 'available']);
        }
    }
}
