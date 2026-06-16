<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('check_in_date');
            $table->enum('status', ['checked_in', 'lunch_break', 'checked_out'])->default('checked_in');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->time('lunch_start_time')->nullable();
            $table->time('lunch_end_time')->nullable();
            $table->unsignedInteger('total_working_seconds')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'check_in_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
