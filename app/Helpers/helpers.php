<?php

declare(strict_types=1);

use App\Fluent\Image;
use App\Helpers\Str;

if (! function_exists('mix_file')) {
    function mix_file(string $file): ?string
    {
        // Ask Laravel Mix for the file
        $url = (string) mix($file);
        if (empty($url)) {
            return null;
        }

        // Strip the default URL
        $path = Str::after($url, (string) app('config')->get('app.mix_url'));
        if (empty($path)) {
            return null;
        }

        // Edge case for hot builds
        if (Str::startsWith($path, ['https://', 'http://'])) {
            $path = parse_url($path, PHP_URL_PATH);
        }

        // Remove queries
        $path = Str::before($path, '?');

        // Convert to public path
        $fullPath = public_path($path);
        if (! file_exists($fullPath)) {
            return null;
        }

        // Get contents
        return file_get_contents($fullPath, false, null, 0, 1024 * 1024);
    }
}

if (! function_exists('image_asset')) {
    function image_asset(?string $file): Image
    {
        return Image::make($file);
    }
}
