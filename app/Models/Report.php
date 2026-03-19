<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'start_date',
        'end_date',
        'data',
        'generated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'data' => 'array',
    ];

    // Relationships
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeDaily($query)
    {
        return $query->where('report_type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('report_type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('report_type', 'monthly');
    }
}