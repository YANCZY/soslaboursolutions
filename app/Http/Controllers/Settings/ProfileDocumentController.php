<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfileFileCleanup;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfileDocumentController extends Controller
{
    public const MAX_FILES = 15;

    public function store(Request $request,ProfileFileCleanup $cleanup,): RedirectResponse
    {
        $validated = $request->validate([
            'category' => [
                'required',
                Rule::in(['resume', 'license', 'certification']),
            ],
            'documents' => [
                'required',
                'array',
                'min:1',
                'max:'.self::MAX_FILES,
            ],
            'documents.*' => [
                'bail',
                'required',
                'file',
                'mimes:pdf,png,jpg,jpeg,webp,svg',
                'extensions:pdf,png,jpg,jpeg,webp,svg',
                'max:10240',
            ],
        ], [
            'documents.required' => 'Please select at least one file.',
            'documents.max' => 'You can attach a maximum of 15 files.',
            'documents.*.mimes' =>
                'Only PDF, PNG, JPG, JPEG, WebP and SVG files are accepted.',
            'documents.*.extensions' =>
                'Please choose PDF, PNG, JPG, JPEG, WebP or SVG files.',
            'documents.*.max' => 'Each file must be 10 MB or smaller.',
            'documents.*.uploaded' =>
                'A file could not be uploaded. Each file must be 10 MB or smaller.',
        ]);

        $userId = $request->user()->id;
        $uploads = [];

        try {
            foreach ($request->file('documents', []) as $index => $file) {
                $mime = $file->getMimeType();

                $extension = match ($mime) {
                    'application/pdf' => 'pdf',
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    'image/webp' => 'webp',
                    'image/svg+xml' => 'svg',
                    default => null,
                };

                if (! $extension) {
                    throw ValidationException::withMessages([
                        "documents.{$index}" =>
                            'Unsupported format: '.$file->getClientOriginalName(),
                    ]);
                }

                $path = $userId.'/'.Str::uuid().'.'.$extension;

                $uploads[] = [
                    'file' => $file,
                    'mime' => $mime,
                    'extension' => $extension,
                    'path' => $path,
                    'staged_id' => $cleanup->remember(
                        'profile_documents',
                        $path,
                        stagedUpload: true,
                    ),
                ];
            }

            DB::transaction(function () use (
                $cleanup,
                $uploads,
                $userId,
                $validated,
            ) {
                foreach ($uploads as $upload) {
                    $cleanup->lockStagedUpload($upload['staged_id']);
                }

                User::query()
                    ->lockForUpdate()
                    ->findOrFail($userId);

                $existingCount = DB::table('profile_documents')
                    ->where('user_id', $userId)
                    ->count();

                if ($existingCount + count($uploads) > self::MAX_FILES) {
                    $remaining = max(0, self::MAX_FILES - $existingCount);

                    throw ValidationException::withMessages([
                        'documents' =>
                            "You can attach up to 15 files. You have {$remaining} available slots.",
                    ]);
                }

                foreach ($uploads as $index => $upload) {
                    $file = $upload['file'];
                    $contents = $file->getContent();

                    if ($upload['extension'] === 'svg') {
                        if (strlen($contents) > 1024 * 1024) {
                            throw ValidationException::withMessages([
                                "documents.{$index}" =>
                                    $file->getClientOriginalName()
                                    .': SVG files must be 1 MB or smaller.',
                            ]);
                        }

                        $sanitizer = new Sanitizer();
                        $sanitizer->removeRemoteReferences(true);

                        $contents = $sanitizer->sanitize($contents);

                        if ($contents === false || trim($contents) === '') {
                            throw ValidationException::withMessages([
                                "documents.{$index}" =>
                                    $file->getClientOriginalName()
                                    .': this SVG could not be processed safely.',
                            ]);
                        }
                    }

                    Storage::disk('profile_documents')->put(
                        $upload['path'],
                        $contents,
                    );

                    DB::table('profile_documents')->insert([
                        'user_id' => $userId,
                        'category' => $validated['category'],
                        'original_name' => Str::limit(
                            $file->getClientOriginalName(),
                            200,
                            '',
                        ),
                        'path' => $upload['path'],
                        'mime_type' => $upload['mime'],
                        'size' => strlen($contents),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $cleanup->keepUpload($upload['staged_id']);
                }
            });
        } catch (Throwable $exception) {
            foreach ($uploads as $upload) {
                $cleanup->attempt($upload['staged_id']);
            }

            throw $exception;
        }

        return back();
    }

    public function show(
        Request $request,
        int $document,
    ): BinaryFileResponse {
        $record = DB::table('profile_documents')
            ->where('user_id', $request->user()->id)
            ->where('id', $document)
            ->first();

        abort_unless($record, 404);

        $disk = Storage::disk('profile_documents');

        abort_unless($disk->exists($record->path), 404);

        $response = response()->file($disk->path($record->path), [
            'Content-Type' => $record->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);

        $response->setContentDisposition(
            'inline',
            basename($record->path),
        );

        $response->headers->set('Cache-Control', 'private, no-store');

        if ($record->mime_type === 'image/svg+xml') {
            $response->headers->set(
                'Content-Security-Policy',
                "sandbox; default-src 'none'; "
                ."style-src 'unsafe-inline'; img-src data:",
            );
        }

        return $response;
    }

    public function destroy(
        Request $request,
        int $document,
        ProfileFileCleanup $cleanup,
    ): RedirectResponse {
        $deletionId = DB::transaction(function () use (
            $request,
            $document,
            $cleanup,
        ) {
            User::query()
                ->lockForUpdate()
                ->findOrFail($request->user()->id);

            $record = DB::table('profile_documents')
                ->where('user_id', $request->user()->id)
                ->where('id', $document)
                ->lockForUpdate()
                ->first();

            abort_unless($record, 404);

            $deletionId = $cleanup->remember(
                'profile_documents',
                $record->path,
            );

            DB::table('profile_documents')
                ->where('id', $record->id)
                ->delete();

            return $deletionId;
        });

        $deleted = $cleanup->attempt($deletionId);

        Inertia::flash('toast', [
            'type' => $deleted ? 'success' : 'info',
            'message' => $deleted
                ? 'File removed.'
                : 'Removed from your profile. Storage cleanup will retry automatically.',
        ]);

        return back();
    }
}
