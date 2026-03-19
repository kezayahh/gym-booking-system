@extends('layouts.admin')

@section('title', 'View Schedule - City Gymnasium')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Schedule Details
    </h1>
    <p class="text-gray-600 mt-1">View full information about this schedule</p>
</div>

<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-500 mb-1">Date</h3>
            <p class="text-lg font-bold text-gray-900">
                {{ \Carbon\Carbon::parse($schedule->date)->format('M d, Y') }}
                <span class="block text-sm text-gray-500">
                    {{ \Carbon\Carbon::parse($schedule->date)->format('l') }}
                </span>
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-500 mb-1">Time Slot</h3>
            <p class="text-lg font-bold text-gray-900">
                {{ $schedule->time_slot ?? ($schedule->start_time . ' - ' . $schedule->end_time) }}
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-500 mb-1">Capacity</h3>
            <p class="text-lg font-bold text-gray-900">
                {{ $schedule->booked_slots ?? 0 }}/{{ $schedule->total_capacity }}
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-500 mb-1">Price per Slot</h3>
            <p class="text-lg font-bold text-gray-900">
                ₱{{ number_format($schedule->price_per_slot, 2) }}
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-500 mb-1">Status</h3>
            @php
                $status = $schedule->status;
            @endphp
            @if($status === 'available')
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    Available
                </span>
            @elseif($status === 'full')
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                    Full
                </span>
            @else
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                    {{ ucfirst($status) }}
                </span>
            @endif
        </div>

        @if(!empty($schedule->notes))
        <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-500 mb-1">Notes</h3>
            <p class="text-sm text-gray-800 whitespace-pre-line">
                {{ $schedule->notes }}
            </p>
        </div>
        @endif
    </div>
</div>

<div>
    <a href="{{ route('admin.schedules') }}" 
       class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
        <i class="fas fa-arrow-left mr-2"></i> Back to Schedules
    </a>
</div>
@endsection
