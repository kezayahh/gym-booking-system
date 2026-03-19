<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // daily, weekly, monthly, custom
            $table->date('start_date');
            $table->date('end_date');
            $table->json('data'); // Store report data as JSON
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('report_type');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

