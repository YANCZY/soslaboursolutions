<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProfileFileCleanup
{
    public function remember(
        string $disk,
        string $path,
        bool $stagedUpload = false,
    ): int {
        return DB::table('pending_file_deletions')->insertGetId([
            'disk' => $disk,
            'path' => $path,
            'available_at' => $stagedUpload
                ? now()->addHour()
                : now(),
        ]);
    }

    public function lockStagedUpload(int $id): void
    {
        $entry = DB::table('pending_file_deletions')
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        if (! $entry) {
            throw new RuntimeException('Upload expired. Please retry.');
        }
    }

    public function keepUpload(int $id): void
    {
        DB::table('pending_file_deletions')
            ->where('id', $id)
            ->delete();
    }

    public function imageDisk(string $path): string
    {
        return str_starts_with($path, 'profile-images/')
            ? 'public'
            : 'profile_images';
    }

    public function attempt(int $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $entry = DB::table('pending_file_deletions')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (! $entry) {
                    return true;
                }

                $disk = Storage::disk($entry->disk);

                if ($disk->exists($entry->path)
                    && ! $disk->delete($entry->path)) {
                    throw new RuntimeException(
                        'Unable to delete a profile file.',
                    );
                }

                DB::table('pending_file_deletions')
                    ->where('id', $id)
                    ->delete();

                return true;
            });
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
