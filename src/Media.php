<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Blocks store an image as the path the upload returned. Turning that into a
 * URL is the renderer's job, not each Blade view's, or twelve views end up
 * with twelve slightly different versions of it.
 */
class Media
{
    /** A file upload field wired to the configured disk. */
    public static function upload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk(config('atelier.media.disk'))
            ->directory(config('atelier.media.directory'))
            ->visibility('public');
    }

    /**
     * Path to URL. Passes through anything that is already a URL.
     *
     * Takes mixed on purpose. Filament's FileUpload holds an array keyed by
     * uuid while editing and stores [] when empty, so a block attribute is
     * not reliably a string. Every block view goes through here, so the
     * unwrapping belongs here rather than in nine Blade files.
     */
    public static function url(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (! is_string($path) || blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        return Storage::disk(config('atelier.media.disk'))->url($path);
    }
}
