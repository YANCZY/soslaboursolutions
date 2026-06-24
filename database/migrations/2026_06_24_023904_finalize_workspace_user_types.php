<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('user_types')
        ->where('user_type_name', 'Admin')
        ->update(['user_type_name' => 'Superadmin']);

        DB::table('user_types')->insertOrIgnore([
            ['user_type_name' => 'Superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['user_type_name' => 'SOS Admin', 'created_at' => now(), 'updated_at' => now()],
            ['user_type_name' => 'SOS Standard', 'created_at' => now(), 'updated_at' => now()],
            ['user_type_name' => 'Client Admin', 'created_at' => now(), 'updated_at' => now()],
            ['user_type_name' => 'Client Standard', 'created_at' => now(), 'updated_at' => now()],
            ['user_type_name' => 'Contractor', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
