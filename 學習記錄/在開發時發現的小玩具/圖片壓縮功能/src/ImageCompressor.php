<?php

/**
 * 圖片壓縮模組 — 核心類別
 *
 * fit_mode：stretch | contain | cover | pad_transparent | pad_fill
 * （pad 為 pad_transparent 別名）
 */

declare(strict_types=1);

namespace ReferenceModules\ImageCompress;

use Illuminate\Http\UploadedFile;

final class ImageCompressor
{
    public const FIT_STRETCH = 'stretch';

    public const FIT_CONTAIN = 'contain';

    public const FIT_COVER = 'cover';

    /** @deprecated 請改用 FIT_PAD_TRANSPARENT */
    public const FIT_PAD = 'pad';

    /** 等比置中 + 透明底（去背畫布，無色邊） */
    public const FIT_PAD_TRANSPARENT = 'pad_transparent';

    /** 等比置中 + 填色底（白邊／色邊，用 pad_color） */
    public const FIT_PAD_FILL = 'pad_fill';

    /**
     * @param  array{
     *     enabled?: bool,
     *     max_width?: int,
     *     max_height?: int,
     *     max_edge?: int,
     *     fit_mode?: string,
     *     pad_color?: string,
     *     jpeg_quality?: int,
     * }  $options
     * @return array{
     *     contents: string,
     *     extension: string,
     *     mime_type: string,
     * }|null
     */
    public static function compress(UploadedFile $file, array $options = []): ?array
    {
        $enabled = $options['enabled'] ?? true;
        $maxWidth = max(0, (int) ($options['max_width'] ?? 0));
        $maxHeight = max(0, (int) ($options['max_height'] ?? 0));
        $maxEdge = max(0, (int) ($options['max_edge'] ?? 1920));
        $fitMode = self::normalizeFitMode($options['fit_mode'] ?? self::FIT_STRETCH);
        $padColor = (string) ($options['pad_color'] ?? '#FFFFFF');
        $quality = max(1, min(100, (int) ($options['jpeg_quality'] ?? 82)));

        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
        );
        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));

        if (! self::shouldCompress($enabled, $mime, $extension)) {
            return null;
        }

        $sourcePath = $file->getRealPath();
        if ($sourcePath === false) {
            return null;
        }

        $image = self::loadGdImage($sourcePath, $extension);
        if ($image === null) {
            return null;
        }

        $image = self::resizeGdImage($image, $maxWidth, $maxHeight, $maxEdge, $fitMode, $padColor);

        return self::encodeGdImage($image, $extension, $quality);
    }

    /**
     * @return list<string>
     */
    public static function fitModes(): array
    {
        return [
            self::FIT_STRETCH,
            self::FIT_CONTAIN,
            self::FIT_COVER,
            self::FIT_PAD_TRANSPARENT,
            self::FIT_PAD_FILL,
        ];
    }

    public static function shouldCompress(bool $enabled, string $mime, string $extension): bool
    {
        if (! $enabled) {
            return false;
        }
        if (! extension_loaded('gd')) {
            return false;
        }
        if (str_starts_with($mime, 'image/')) {
            return ! in_array($mime, ['image/gif', 'image/svg+xml'], true);
        }

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'bmp'], true);
    }

    public static function normalizeFitMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        if ($mode === self::FIT_PAD) {
            return self::FIT_PAD_TRANSPARENT;
        }

        return in_array($mode, self::fitModes(), true)
            ? $mode
            : self::FIT_STRETCH;
    }

    public static function isPadMode(string $mode): bool
    {
        $mode = self::normalizeFitMode($mode);

        return in_array($mode, [self::FIT_PAD_TRANSPARENT, self::FIT_PAD_FILL], true);
    }

    /**
     * @return array{contents: string, extension: string, mime_type: string}|null
     */
    private static function encodeGdImage($image, string $extension, int $quality): ?array
    {
        ob_start();

        if ($extension === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 6);
            $contents = (string) ob_get_clean();
            imagedestroy($image);

            return $contents === '' ? null : [
                'contents' => $contents,
                'extension' => 'png',
                'mime_type' => 'image/png',
            ];
        }

        if ($extension === 'webp' && function_exists('imagewebp')) {
            imagewebp($image, null, $quality);
            $contents = (string) ob_get_clean();
            imagedestroy($image);

            return $contents === '' ? null : [
                'contents' => $contents,
                'extension' => 'webp',
                'mime_type' => 'image/webp',
            ];
        }

        imagejpeg($image, null, $quality);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        return $contents === '' ? null : [
            'contents' => $contents,
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
        ];
    }

    /**
     * @return resource|\GdImage|null
     */
    private static function loadGdImage(string $path, string $extension)
    {
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'png' => @imagecreatefrompng($path) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            'bmp' => function_exists('imagecreatefrombmp') ? (@imagecreatefrombmp($path) ?: null) : null,
            'gif' => @imagecreatefromgif($path) ?: null,
            default => null,
        };
    }

    /**
     * @param  resource|\GdImage  $image
     * @return resource|\GdImage
     */
    private static function resizeGdImage($image, int $maxWidth, int $maxHeight, int $maxEdge, string $fitMode, string $padColor)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0) {
            return $image;
        }

        if ($maxWidth > 0 || $maxHeight > 0) {
            [$targetW, $targetH] = self::resolveTargetDimensions($width, $height, $maxWidth, $maxHeight);

            $result = match ($fitMode) {
                self::FIT_CONTAIN => self::fitContain($image, $targetW, $targetH),
                self::FIT_COVER => self::fitCover($image, $targetW, $targetH),
                self::FIT_PAD_TRANSPARENT => self::fitPad($image, $targetW, $targetH, 'transparent'),
                self::FIT_PAD_FILL => self::fitPad($image, $targetW, $targetH, $padColor),
                default => self::fitStretch($image, $targetW, $targetH),
            };
            if ($result !== $image) {
                imagedestroy($image);
            }

            return $result;
        }

        $scaled = self::scaleProportionalLimit($image, $maxEdge);
        if ($scaled !== $image) {
            imagedestroy($image);
        }

        return $scaled;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function resolveTargetDimensions(int $srcW, int $srcH, int $maxWidth, int $maxHeight): array
    {
        return [
            $maxWidth > 0 ? $maxWidth : $srcW,
            $maxHeight > 0 ? $maxHeight : $srcH,
        ];
    }

    /**
     * @param  resource|\GdImage  $image
     * @return resource|\GdImage
     */
    private static function fitStretch($image, int $targetW, int $targetH)
    {
        return self::resampleToCanvas($image, 0, 0, imagesx($image), imagesy($image), $targetW, $targetH);
    }

    /**
     * @param  resource|\GdImage  $image
     * @return resource|\GdImage
     */
    private static function fitContain($image, int $targetW, int $targetH)
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $ratio = min($targetW / $srcW, $targetH / $srcH);
        $newW = max(1, (int) round($srcW * $ratio));
        $newH = max(1, (int) round($srcH * $ratio));

        return self::resampleToCanvas($image, 0, 0, $srcW, $srcH, $newW, $newH);
    }

    /**
     * @param  resource|\GdImage  $image
     * @return resource|\GdImage
     */
    private static function fitCover($image, int $targetW, int $targetH)
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $ratio = max($targetW / $srcW, $targetH / $srcH);
        $scaledW = max(1, (int) round($srcW * $ratio));
        $scaledH = max(1, (int) round($srcH * $ratio));

        $scaled = self::resampleToCanvas($image, 0, 0, $srcW, $srcH, $scaledW, $scaledH);

        $cropX = max(0, (int) floor(($scaledW - $targetW) / 2));
        $cropY = max(0, (int) floor(($scaledH - $targetH) / 2));

        $result = self::resampleToCanvas($scaled, $cropX, $cropY, $targetW, $targetH, $targetW, $targetH);
        imagedestroy($scaled);

        return $result;
    }

    /**
     * @param  resource|\GdImage  $image
     * @return resource|\GdImage
     */
    private static function fitPad($image, int $targetW, int $targetH, string $padColor)
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $ratio = min($targetW / $srcW, $targetH / $srcH);
        $newW = max(1, (int) round($srcW * $ratio));
        $newH = max(1, (int) round($srcH * $ratio));

        $scaled = self::resampleToCanvas($image, 0, 0, $srcW, $srcH, $newW, $newH);

        $canvas = imagecreatetruecolor($targetW, $targetH);
        self::fillCanvasBackground($canvas, $targetW, $targetH, $padColor);

        $dstX = (int) floor(($targetW - $newW) / 2);
        $dstY = (int) floor(($targetH - $newH) / 2);
        imagecopy($canvas, $scaled, $dstX, $dstY, 0, 0, $newW, $newH);
        imagedestroy($scaled);

        return $canvas;
    }

    /**
     * @param  resource|\GdImage  $canvas
     */
    private static function fillCanvasBackground($canvas, int $width, int $height, string $padColor): void
    {
        if (self::isTransparentPadColor($padColor)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            imagealphablending($canvas, true);

            return;
        }

        [$r, $g, $b] = self::parseHexColor($padColor);
        $bg = imagecolorallocate($canvas, $r, $g, $b);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $bg);
    }

    private static function isTransparentPadColor(string $padColor): bool
    {
        $value = strtolower(trim($padColor));

        return in_array($value, ['transparent', 'none', ''], true);
    }

    /**
     * @param  resource|\GdImage  $image
     * @return resource|\GdImage
     */
    private static function scaleProportionalLimit($image, int $maxEdge)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($maxEdge <= 0) {
            return $image;
        }

        $longest = max($width, $height);
        if ($longest <= $maxEdge) {
            return $image;
        }

        $ratio = $maxEdge / $longest;
        $newW = max(1, (int) round($width * $ratio));
        $newH = max(1, (int) round($height * $ratio));

        return self::resampleToCanvas($image, 0, 0, $width, $height, $newW, $newH);
    }

    /**
     * @param  resource|\GdImage  $source
     * @return resource|\GdImage
     */
    private static function resampleToCanvas($source, int $srcX, int $srcY, int $srcW, int $srcH, int $dstW, int $dstH)
    {
        $canvas = imagecreatetruecolor($dstW, $dstH);
        imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        return $canvas;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function parseHexColor(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return [255, 255, 255];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
