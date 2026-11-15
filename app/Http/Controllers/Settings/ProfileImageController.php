<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use App\Services\ProfileFileCleanup;
use Throwable;

class ProfileImageController extends Controller
{
    public function store(Request $request, ProfileFileCleanup $cleanup,): RedirectResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['photo', 'banner'])],
            'image' => [
                'bail',
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'extensions:jpg,jpeg,png',
                'max:5120',
                'dimensions:max_width=4096,max_height=4096',
            ],
        ], [
            'image.required' => 'Please choose an image.',
            'image.file' => 'Please choose a valid image file.',
            'image.image' => 'Please upload a valid PNG or JPEG image.',
            'image.mimes' => 'Only PNG and JPEG (.png, .jpg, .jpeg) images are accepted. Please export your image in one of these formats.',
            'image.extensions' => 'Only .png, .jpg and .jpeg files are accepted.',
            'image.max' => 'Your image must be 5 MB or smaller.',
            'image.dimensions' => 'Your image must not exceed 4096 pixels in width or height.',
            'image.uploaded' => 'The image could not be uploaded. Please choose a PNG or JPEG image up to 5 MB.',
        ]);

        $userId = $request->user()->id;

        $column = $validated['kind'] === 'photo'
            ? 'profile_photo_path'
            : 'profile_banner_path';

        $isPhoto = $validated['kind'] === 'photo';

        $width = $isPhoto ? 400 : 1200;
        $height = $isPhoto ? 400 : 300;
        $maxBytes = ($isPhoto ? 150 : 300) * 1024;

        $manager = ImageManager::usingDriver(
            Driver::class,
            autoOrientation: true,
            decodeAnimation: false,
            backgroundColor: 'ffffff',
            strip: true,
        );

        try {
            $image = $manager->decodePath(
                $request->file('image')->getPathname(),
            );
        } catch (DecoderException $exception) {
            throw ValidationException::withMessages([
                'image' => 'This image could not be read. Please export it again as PNG or JPEG and retry.',
            ]);
        }

        $image->coverDown($width, $height);

        $contents = '';

        foreach ([80, 70, 60] as $quality) {
            $contents = (string) $image->encodeUsingFormat(
                Format::JPEG,
                quality: $quality,
                progressive: true,
            );

            if (strlen($contents) <= $maxBytes) {
                break;
            }
        }

        if (strlen($contents) > $maxBytes) {
            throw ValidationException::withMessages([
                'image' => 'This image could not be optimized within the storage limit. Please choose a simpler image or export a smaller version.',
            ]);
        }

        $newPath = $userId.'/'.Str::uuid().'.jpg';

        $stagedId = $cleanup->remember(
            'profile_images',
            $newPath,
            stagedUpload: true,
        );

        try {
            $oldDeletionId = DB::transaction(function () use (
                $cleanup,
                $stagedId,
                $userId,
                $column,
                $newPath,
                $contents,
            ) {
                $cleanup->lockStagedUpload($stagedId);

                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($userId);

                Storage::disk('profile_images')->put($newPath, $contents);

                $oldPath = $user->{$column};

                $user->{$column} = $newPath;
                $user->save();

                $oldDeletionId = $oldPath
                    ? $cleanup->remember(
                        $cleanup->imageDisk($oldPath),
                        $oldPath,
                    )
                    : null;

                $cleanup->keepUpload($stagedId);

                return $oldDeletionId;
            });
        } catch (Throwable $exception) {
            $cleanup->attempt($stagedId);

            throw $exception;
        }

        if ($oldDeletionId) {
            $cleanup->attempt($oldDeletionId);
        }

        return back();
    }


}
