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
        'price_per_slot' => 'decimal:2',
        'total_capacity' => 'integer',
        'booked_slots' => 'integer',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->whereColumn('booked_slots', '<', 'total_capacity');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('date', '>=', Carbon::today());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', Carbon::today());
    }

    public function scopePast($query)
    {
        return $query->whereDate('date', '<', Carbon::today());
    }

    public function getDurationHoursAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end) / 60;
    }

    public function getTotalPriceAttribute()
    {
        return (float) $this->duration_hours * (float) $this->price_per_slot;
    }

    public function getAvailableSlotsAttribute()
    {
        return max(0, (int) $this->total_capacity - (int) $this->booked_slots);
    }

    public function getIsFullAttribute()
    {
        return (int) $this->booked_slots >= (int) $this->total_capacity;
    }

    public function getTimeSlotAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return '';
        }

        return Carbon::parse($this->start_time)->format('h:i A') . ' - ' .
               Carbon::parse($this->end_time)->format('h:i A');
    }

    public function canBook($numberOfSlots = 1)
    {
        return $this->status === 'available'
            && ((int) $this->booked_slots + (int) $numberOfSlots) <= (int) $this->total_capacity
            && Carbon::parse($this->date)->gte(Carbon::today());
    }

    public function incrementBookedSlots($numberOfSlots = 1)
    {
        $this->booked_slots = (int) $this->booked_slots + (int) $numberOfSlots;

        if ($this->booked_slots >= $this->total_capacity) {
            $this->status = 'full';
        } else {
            $this->status = 'available';
        }

        $this->save();

        return $this;
    }

    public function decrementBookedSlots($numberOfSlots = 1)
    {
        $this->booked_slots = max(0, (int) $this->booked_slots - (int) $numberOfSlots);

        if ($this->booked_slots < $this->total_capacity && $this->status === 'full') {
            $this->status = 'available';
        }

        if ($this->booked_slots < $this->total_capacity && $this->status !== 'closed') {
            $this->status = 'available';
        }

        $this->save();

        return $this;
    }
}