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
        Schema::create('user_company_work_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('job_role')->nullable();
            $table->decimal('salary', 10, 2)->default(0);
            $table->decimal('travel_allowance', 10, 2)->default(0);
            $table->string('travel_allowance_currency', 3)->default('AUD');
            $table->time('start_shift')->nullable();
            $table->time('end_shift')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_company_work_details');
    }
};
