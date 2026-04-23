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
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->default('')->after('id');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->default('')->after('first_name');
            }
        });

        if (Schema::hasColumn('users', 'name')) {
            DB::table('users')
                ->select(['id', 'name'])
                ->orderBy('id')
                ->get()
                ->each(function (object $user): void {
                    $name = trim((string) $user->name);

                    if ($name === '') {
                        return;
                    }

                    $parts = preg_split('/\s+/', $name) ?: [];
                    $firstName = array_shift($parts) ?? '';
                    $lastName = implode(' ', $parts);

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                        ]);
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->default('')->after('id');
            });
        }

        if (Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
            DB::table('users')
                ->select(['id', 'first_name', 'last_name'])
                ->orderBy('id')
                ->get()
                ->each(function (object $user): void {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'name' => trim(implode(' ', array_filter([
                                $user->first_name,
                                $user->last_name,
                            ]))),
                        ]);
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['first_name', 'last_name']);
            });
        }
    }
};
