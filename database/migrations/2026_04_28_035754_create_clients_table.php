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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_address')->nullable();
            $table->string('company_address_2')->nullable();
            $table->string('company_address_city')->nullable();
            $table->string('company_address_state')->nullable();
            $table->string('company_address_zip')->nullable();
            $table->string('company_address_country')->nullable();
            $table->string('plant_address')->nullable();
            $table->string('plant_address_2')->nullable();
            $table->string('plant_address_city')->nullable();
            $table->string('plant_address_state')->nullable();
            $table->string('plant_address_zip')->nullable();
            $table->string('plant_address_country')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
