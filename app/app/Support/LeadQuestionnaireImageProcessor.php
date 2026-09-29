<?php

namespace App\Support;

use App\Models\Lead;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class LeadQuestionnaireImageProcessor
{
    public const MAX_LONG_SIDE = 1600;

    public function store(Lead $lead, UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath());
        $source = $contents !== false && extension_loaded('gd')
            ? @imagecreatefromstring($contents)
            : false;

        if (! $source) {
            $path = $file->storePublicly(
                sprintf('leads/%d/questionnaire-inspirations', $lead->getKey()),
                'public',
            );

            if (! is_string($path)) {
                throw new RuntimeException('The inspiration image could not be stored.');
            }

            return $path;
        }

        $source = $this->applyExifOrientation($source, $file);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, self::MAX_LONG_SIDE / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $mimeType = (string) $file->getMimeType();

        if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefill($target, 0, 0, $transparent);
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        [$extension, $encoded] = $this->encode($target, $mimeType);

        imagedestroy($source);
        imagedestroy($target);

        if ($encoded === '') {
            throw new RuntimeException('The inspiration image could not be processed.');
        }

        $path = sprintf(
            'leads/%d/questionnaire-inspirations/%s.%s',
            $lead->getKey(),
            Str::uuid(),
            $extension,
        );

        if (! Storage::disk('public')->put($path, $encoded)) {
            throw new RuntimeException('The inspiration image could not be stored.');
        }

        return $path;
    }

    protected function applyExifOrientation(mixed $image, UploadedFile $file): mixed
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if (! $rotated) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /** @return array{0: string, 1: string} */
    protected function encode(mixed $image, string $mimeType): array
    {
        ob_start();

        if ($mimeType === 'image/png') {
            imagepng($image, null, 8);
            $extension = 'png';
        } elseif ($mimeType === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($image, null, 88);
            $extension = 'webp';
        } else {
            imagejpeg($image, null, 88);
            $extension = 'jpg';
        }

        $contents = ob_get_clean();

        return [$extension, is_string($contents) ? $contents : ''];
    }
}
