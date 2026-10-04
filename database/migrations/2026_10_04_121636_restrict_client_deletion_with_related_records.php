<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORIGINAL_DELETE_ACTIONS = [
        'users' => 'set null',
        'client_user' => 'cascade',
        'user_company_work_details' => 'cascade',
        'attendance' => 'set null',
        'travel_allowances' => 'cascade',
        'client_save_requests' => 'set null',
    ];

    public function up(): void
    {
        foreach (self::ORIGINAL_DELETE_ACTIONS as $tableName => $action) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['client_id']);

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::ORIGINAL_DELETE_ACTIONS as $tableName => $action) {
            Schema::table($tableName, function (Blueprint $table) use ($action) {
                $table->dropForeign(['client_id']);

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete($action);
            });
        }
    }

};
