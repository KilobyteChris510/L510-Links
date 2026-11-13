<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLinkGroupAppearanceRequest;
use App\Models\LinkGroup;
use GdImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LinkGroupAppearanceController extends Controller
{
    private const AVATAR_SIZE = 512;

    private const AVATAR_DIRECTORY = 'avatars';

    public function edit(LinkGroup $group): View
    {
        Gate::authorize('update', $group);

        return view('groups.appearance', [
            'group' => $group,
            'links' => $group->links()->where('is_active', true)->get(),
        ]);
    }

    public function update(UpdateLinkGroupAppearanceRequest $request, LinkGroup $group): RedirectResponse
    {
        $theme = $request->validated('theme');
        $values = array_map(strtolower(...), $request->validated('appearance'));

        // Persist only values that differ from the theme, so theme defaults stay authoritative.
        $group->theme = $theme;
        $group->appearance = array_diff_assoc($values, LinkGroup::themeDefaults($theme)) ?: null;

        $previousAvatar = $group->avatar_path;
        $storedAvatar = null;

        if ($request->hasFile('avatar')) {
            $storedAvatar = $this->storeAvatar($request->file('avatar'));
            $group->avatar_path = $storedAvatar;
        } elseif ($request->boolean('remove_avatar')) {
            $group->avatar_path = null;
        }

        try {
            $group->save();
        } catch (Throwable $e) {
            if ($storedAvatar) {
                Storage::disk('public')->delete($storedAvatar);
            }

            throw $e;
        }

        if ($previousAvatar && $previousAvatar !== $group->avatar_path) {
            $this->deleteAvatar($previousAvatar);
        }

        return to_route('groups.appearance.edit', $group)->with('status', 'Appearance saved.');
    }

    /**
     * Decode with GD and re-encode as a square WebP under a random name. Re-encoding
     * rejects files that are not real images and strips metadata such as GPS tags.
     */
    private function storeAvatar(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages([
                'avatar' => 'The profile image could not be read. Upload a JPEG, PNG, or WebP file.',
            ]);
        }

        $source = $this->applyExifOrientation($source, $file);

        $side = min(imagesx($source), imagesy($source));
        $size = min(self::AVATAR_SIZE, $side);

        $avatar = imagecreatetruecolor($size, $size);
        imagealphablending($avatar, false);
        imagesavealpha($avatar, true);
        imagefill($avatar, 0, 0, imagecolorallocatealpha($avatar, 0, 0, 0, 127));
        imagecopyresampled(
            $avatar, $source, 0, 0,
            intdiv(imagesx($source) - $side, 2), intdiv(imagesy($source) - $side, 2),
            $size, $size, $side, $side,
        );

        ob_start();
        imagewebp($avatar, null, 85);
        $contents = (string) ob_get_clean();

        $path = self::AVATAR_DIRECTORY.'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    private function applyExifOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $angle = match (@exif_read_data($file->getRealPath())['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /**
     * Only files this controller manages are deleted, never arbitrary stored paths.
     */
    private function deleteAvatar(string $path): void
    {
        if (str_starts_with($path, self::AVATAR_DIRECTORY.'/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
