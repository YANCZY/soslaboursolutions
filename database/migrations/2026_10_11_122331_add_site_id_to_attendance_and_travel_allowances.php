<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_company_work_details', function (Blueprint $table) {
            $table->foreignId('site_id')
                ->nullable()
                ->constrained('sites')
                ->restrictOnDelete();

            $table->dropUnique(
                'user_company_work_details_user_id_client_id_unique'
            );
        });

        DB::statement(
            'CREATE UNIQUE INDEX ucwd_main_unique
             ON user_company_work_details (user_id, client_id)
             WHERE site_id IS NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX ucwd_site_unique
             ON user_company_work_details (user_id, site_id)
             WHERE site_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        if (
            DB::table('user_company_work_details')
                ->whereNotNull('site_id')
                ->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back while site work details exist.'
            );
        }

        DB::statement('DROP INDEX ucwd_main_unique');
        DB::statement('DROP INDEX ucwd_site_unique');

        Schema::table('user_company_work_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
            $table->unique(['user_id', 'client_id']);
        });
    }
};
