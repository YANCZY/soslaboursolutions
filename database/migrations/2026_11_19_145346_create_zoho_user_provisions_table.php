<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoho_user_provisions', function (Blueprint $table) {
            $table->id();

            $table->string('source_type', 20);
            $table->string('source_id', 100);
            $table->char('request_hash', 64);

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->json('response');
            $table->timestamps();

            $table->unique(
                ['source_type', 'source_id'],
                'zoho_user_provisions_source_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoho_user_provisions');
    }
};
