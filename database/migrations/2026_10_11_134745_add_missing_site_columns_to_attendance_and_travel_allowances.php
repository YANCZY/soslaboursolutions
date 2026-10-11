<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['attendance', 'travel_allowances'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('site_id')
                    ->nullable()
                    ->constrained('sites')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['attendance', 'travel_allowances'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('site_id');
            });
        }
    }
};
