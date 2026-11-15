<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ProfileFileCleanup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('profile-files:cleanup', function () {
    $cleanup = app(ProfileFileCleanup::class);

    DB::table('pending_file_deletions')
        ->where('available_at', '<=', now())
        ->orderBy('id')
        ->chunkById(100, function ($entries) use ($cleanup) {
            foreach ($entries as $entry) {
                $cleanup->attempt($entry->id);
            }
        });
})->purpose('Retry deletion of unused profile files');

Schedule::command('profile-files:cleanup')
    ->everyMinute()
    ->withoutOverlapping();
