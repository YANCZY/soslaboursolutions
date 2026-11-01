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
        foreach (['clients', 'sites'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('geofence_latitude', 10, 7)->nullable();
                $table->decimal('geofence_longitude', 10, 7)->nullable();
                $table->unsignedInteger('geofence_radius_meters')->nullable();
                $table->string('geofence_address_hash', 64)->nullable();
                $table->timestamp('geofence_geocoded_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        foreach (['clients', 'sites'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'geofence_latitude',
                    'geofence_longitude',
                    'geofence_radius_meters',
                    'geofence_address_hash',
                    'geofence_geocoded_at',
                ]);
            });
        }
    }
};
