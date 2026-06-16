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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('company_type')->nullable()->after('company_name');
            $table->string('trade')->nullable()->after('company_type');
            $table->string('industry')->nullable()->after('trade');
            $table->text('industry_description')->nullable()->after('industry');
            $table->string('phone', 20)->nullable()->after('industry_description');
            $table->string('website')->nullable()->after('phone');

            $table->dropColumn([
                'plant_address',
                'plant_address_2',
                'plant_address_city',
                'plant_address_state',
                'plant_address_zip',
                'plant_address_country',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'company_type',
                'trade',
                'industry',
                'industry_description',
                'phone',
                'website',
            ]);

            $table->string('plant_address')->nullable();
            $table->string('plant_address_2')->nullable();
            $table->string('plant_address_city')->nullable();
            $table->string('plant_address_state')->nullable();
            $table->string('plant_address_zip')->nullable();
            $table->string('plant_address_country')->nullable();
        });
    }
};
