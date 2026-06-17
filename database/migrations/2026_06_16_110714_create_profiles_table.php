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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('travel_allowance', 10, 2)->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('users')
            ->select('id')
            ->orderBy('id')
            ->chunk(100, function ($users) use ($now) {
                DB::table('profiles')->insert(
                    $users->map(fn ($user) => [
                        'user_id' => $user->id,
                        'travel_allowance' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
