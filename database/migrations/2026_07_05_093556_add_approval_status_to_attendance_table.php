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
        Schema::table('attendance', function (Blueprint $table) {
            $table->string('approval_status')
                ->nullable()
                ->after('status');

            $table->timestamp('submitted_for_approval_at')
                ->nullable()
                ->after('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'submitted_for_approval_at',
            ]);
        });
    }
};
