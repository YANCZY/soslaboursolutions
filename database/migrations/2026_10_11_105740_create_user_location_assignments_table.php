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
        Schema::create('user_location_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();

            $table->foreignId('site_id')
                ->nullable()
                ->constrained('sites')
                ->restrictOnDelete();

            $table->timestamps();
        });

        // PostgreSQL: prevent duplicate main-company assignments.
        DB::statement(
            'CREATE UNIQUE INDEX user_location_main_unique
            ON user_location_assignments (user_id, client_id)
            WHERE site_id IS NULL'
        );

        // Prevent duplicate site assignments.
        DB::statement(
            'CREATE UNIQUE INDEX user_location_site_unique
            ON user_location_assignments (user_id, site_id)
            WHERE site_id IS NOT NULL'
        );

        // Existing company assignments become main-company assignments.
        DB::table('user_location_assignments')->insertUsing(
            ['user_id', 'client_id', 'created_at', 'updated_at'],
            DB::table('client_user')->select(
                'user_id',
                'client_id',
                'created_at',
                'updated_at',
            ),
        );

        // Include primary company links that are absent from client_user.
        DB::table('user_location_assignments')->insertUsing(
            ['user_id', 'client_id', 'created_at', 'updated_at'],
            DB::table('users')
                ->whereNotNull('users.client_id')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('user_location_assignments as assignments')
                        ->whereColumn('assignments.user_id', 'users.id')
                        ->whereColumn('assignments.client_id', 'users.client_id');
                })
                ->select(
                    'users.id',
                    'users.client_id',
                    'users.created_at',
                    'users.updated_at',
                ),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_location_assignments');
    }
};
