<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('total_capacity')->default(50);
            $table->integer('booked_slots')->default(0);
            $table->decimal('price_per_slot', 10, 2)->default(100.00);
            $table->enum('status', ['available', 'full', 'closed'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('date');
            $table->index('status');
            $table->unique(['date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
