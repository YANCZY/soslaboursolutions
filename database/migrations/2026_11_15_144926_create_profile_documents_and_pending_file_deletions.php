<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('pending_file_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 50);
            $table->string('path');
            $table->timestamp('available_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_documents');
        Schema::dropIfExists('pending_file_deletions');
    }
};
