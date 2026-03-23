<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;

class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:convert-webp {--quality=80 : WebP quality (1-100)}';
    protected $description = 'Convert all PNG/JPG images in public/images to WebP';

    public function handle()
    {
        $quality = (int) $this->option('quality');
        $basePath = public_path('images');
        $converted = 0;
        $skipped = 0;

        $this->info("Converting images in {$basePath} to WebP (quality: {$quality})...");

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'bmp'])) {
                continue;
            }

            $source = $file->getPathname();
            $webpPath = preg_replace('/\.(png|jpg|jpeg|gif|bmp)$/i', '.webp', $source);

            // Skip if WebP already exists and is newer
            if (file_exists($webpPath) && filemtime($webpPath) >= filemtime($source)) {
                $this->line("  ⏭ Skip (exists): " . basename($source));
                $skipped++;
                continue;
            }

            $result = ImageService::convertToWebp($source, $webpPath, $quality);
            if ($result) {
                $origSize = filesize($source);
                $newSize = filesize($webpPath);
                $savings = round((1 - $newSize / max($origSize, 1)) * 100);
                $this->info("  ✅ {$file->getFilename()} → .webp ({$savings}% smaller)");
                $converted++;
            } else {
                $this->warn("  ❌ Failed: {$file->getFilename()}");
            }
        }

        $this->newLine();
        $this->info("Done! Converted: {$converted}, Skipped: {$skipped}");

        return 0;
    }
}
