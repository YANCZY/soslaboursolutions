<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('user_type_id')
                ->nullable()
                ->after('last_name')
                ->constrained('user_types');
        });

        $adminUserTypeId = DB::table('user_types')
            ->where('user_type_name', 'Admin')
            ->value('id');

        if ($adminUserTypeId !== null) {
            DB::table('users')
                ->whereNull('user_type_id')
                ->update(['user_type_id' => $adminUserTypeId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_type_id');
        });
    }
};
