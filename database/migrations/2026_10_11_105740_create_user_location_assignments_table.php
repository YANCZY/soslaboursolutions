<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            throw new RuntimeException('Unsupported database driver.');
        }

        if (! Schema::hasTable('user_location_assignments')) {
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
        }

        if ($driver === 'mysql') {
            if (! Schema::hasColumn('user_location_assignments', 'main_company_id')) {
                Schema::table('user_location_assignments', function (Blueprint $table) {
                    $table->unsignedBigInteger('main_company_id')
                        ->nullable()
                        ->storedAs(
                            'CASE WHEN site_id IS NULL THEN client_id ELSE NULL END'
                        );
                });
            }

            if (! Schema::hasIndex('user_location_assignments', 'user_location_main_unique')) {
                Schema::table('user_location_assignments', function (Blueprint $table) {
                    $table->unique(
                        ['user_id', 'main_company_id'],
                        'user_location_main_unique'
                    );
                });
            }

            if (! Schema::hasIndex('user_location_assignments', 'user_location_site_unique')) {
                Schema::table('user_location_assignments', function (Blueprint $table) {
                    $table->unique(
                        ['user_id', 'site_id'],
                        'user_location_site_unique'
                    );
                });
            }
        } else {
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS user_location_main_unique
                 ON user_location_assignments (user_id, client_id)
                 WHERE site_id IS NULL'
            );

            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS user_location_site_unique
                 ON user_location_assignments (user_id, site_id)
                 WHERE site_id IS NOT NULL'
            );
        }

        // UNION removes duplicate user/company pairs from the two sources.
        $legacyAssignments = DB::table('client_user')
            ->select('user_id', 'client_id')
            ->union(
                DB::table('users')
                    ->whereNotNull('client_id')
                    ->selectRaw('id AS user_id, client_id')
            )
            ->get();

        foreach ($legacyAssignments as $assignment) {
            $exists = DB::table('user_location_assignments')
                ->where('user_id', $assignment->user_id)
                ->where('client_id', $assignment->client_id)
                ->whereNull('site_id')
                ->exists();

            if (! $exists) {
                DB::table('user_location_assignments')->insert([
                    'user_id' => $assignment->user_id,
                    'client_id' => $assignment->client_id,
                    'site_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_location_assignments');
    }
};
