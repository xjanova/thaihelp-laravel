<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Convert and store an uploaded image as WebP.
     */
    public static function storeAsWebp(UploadedFile $file, string $directory = 'uploads', int $quality = 80): ?string
    {
        try {
            $image = self::createImageResource($file->getPathname(), $file->getMimeType());
            if (!$image) return null;

            try {
                $filename = Str::uuid() . '.webp';
                $path = storage_path("app/public/{$directory}/{$filename}");

                // Ensure directory exists
                $dir = dirname($path);
                if (!is_dir($dir)) mkdir($dir, 0775, true);

                imagewebp($image, $path, $quality);

                return "storage/{$directory}/{$filename}";
            } finally {
                imagedestroy($image);
            }
        } catch (\Exception $e) {
            \Log::error('WebP conversion failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convert an existing file to WebP.
     */
    public static function convertToWebp(string $sourcePath, string $destPath = null, int $quality = 80): ?string
    {
        try {
            if (!file_exists($sourcePath)) return null;

            $mime = mime_content_type($sourcePath);
            $image = self::createImageResource($sourcePath, $mime);
            if (!$image) return null;

            $destPath = $destPath ?: preg_replace('/\.(png|jpg|jpeg|gif|bmp)$/i', '.webp', $sourcePath);

            $dir = dirname($destPath);
            if (!is_dir($dir)) mkdir($dir, 0775, true);

            imagewebp($image, $destPath, $quality);
            imagedestroy($image);

            return $destPath;
        } catch (\Exception $e) {
            \Log::error('WebP conversion failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private static function createImageResource(string $path, ?string $mime)
    {
        // Reject oversized images to prevent memory exhaustion
        $info = @getimagesize($path);
        if ($info && ($info[0] > 8000 || $info[1] > 8000)) {
            \Log::warning('Image too large', ['width' => $info[0], 'height' => $info[1]]);
            return null;
        }

        return match (true) {
            str_contains($mime ?? '', 'png') => imagecreatefrompng($path),
            str_contains($mime ?? '', 'jpeg'), str_contains($mime ?? '', 'jpg') => imagecreatefromjpeg($path),
            str_contains($mime ?? '', 'gif') => imagecreatefromgif($path),
            str_contains($mime ?? '', 'webp') => imagecreatefromwebp($path),
            str_contains($mime ?? '', 'bmp') => imagecreatefrombmp($path),
            default => null,
        };
    }
}
