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

        if (! Schema::hasColumn('user_company_work_details', 'site_id')) {
            Schema::table('user_company_work_details', function (Blueprint $table) {
                $table->foreignId('site_id')
                    ->nullable()
                    ->constrained('sites')
                    ->restrictOnDelete();
            });
        }

        if ($driver === 'mysql') {
            if (! Schema::hasColumn('user_company_work_details', 'main_company_id')) {
                Schema::table('user_company_work_details', function (Blueprint $table) {
                    $table->unsignedBigInteger('main_company_id')
                        ->nullable()
                        ->storedAs(
                            'CASE WHEN site_id IS NULL THEN client_id ELSE NULL END'
                        );
                });
            }

            if (! Schema::hasIndex('user_company_work_details', 'ucwd_main_unique')) {
                Schema::table('user_company_work_details', function (Blueprint $table) {
                    $table->unique(
                        ['user_id', 'main_company_id'],
                        'ucwd_main_unique'
                    );
                });
            }

            if (! Schema::hasIndex('user_company_work_details', 'ucwd_site_unique')) {
                Schema::table('user_company_work_details', function (Blueprint $table) {
                    $table->unique(
                        ['user_id', 'site_id'],
                        'ucwd_site_unique'
                    );
                });
            }
        } else {
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS ucwd_main_unique
                 ON user_company_work_details (user_id, client_id)
                 WHERE site_id IS NULL'
            );

            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS ucwd_site_unique
                 ON user_company_work_details (user_id, site_id)
                 WHERE site_id IS NOT NULL'
            );
        }

        // Create the replacement indexes BEFORE removing the old one.
        // MySQL needs an index supporting the user_id foreign key.
        if (Schema::hasIndex(
            'user_company_work_details',
            'user_company_work_details_user_id_client_id_unique'
        )) {
            Schema::table('user_company_work_details', function (Blueprint $table) {
                $table->dropUnique(
                    'user_company_work_details_user_id_client_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (DB::table('user_company_work_details')->whereNotNull('site_id')->exists()) {
            throw new RuntimeException(
                'Cannot roll back while site work details exist.'
            );
        }

        // Restore this first so the user_id foreign key remains indexed.
        Schema::table('user_company_work_details', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'client_id'],
                'user_company_work_details_user_id_client_id_unique'
            );
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('user_company_work_details', function (Blueprint $table) {
                $table->dropUnique('ucwd_main_unique');
                $table->dropUnique('ucwd_site_unique');
            });

            Schema::table('user_company_work_details', function (Blueprint $table) {
                $table->dropColumn('main_company_id');
            });
        } else {
            DB::statement('DROP INDEX IF EXISTS ucwd_main_unique');
            DB::statement('DROP INDEX IF EXISTS ucwd_site_unique');
        }

        Schema::table('user_company_work_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });
    }
};
