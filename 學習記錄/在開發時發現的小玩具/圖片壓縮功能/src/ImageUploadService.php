<?php

/**
 * 圖片上傳服務：上傳 → 壓縮 → 儲存
 *
 * 用於多媒體管理，與 Order、單據無關。
 *
 * C 層用法：
 *
 *   $this->imageUpload->fitMode = 'stretch';
 *   $this->imageUpload->maxWidth = 1025;
 *   $this->imageUpload->maxHeight = 900;
 *   $saved = $this->imageUpload->upload($request->file('images', []));
 *
 * 或一次傳參：
 *
 *   $saved = $this->imageUpload->upload($files, 'pad_transparent', 1025, 900);
 *
 * @see ImageCompressor 底層壓縮引擎
 */

declare(strict_types=1);

namespace ReferenceModules\ImageCompress;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    public string $fitMode = ImageCompressor::FIT_STRETCH;

    /** 0 = 沿用原圖寬 */
    public int $maxWidth = 0;

    /** 0 = 沿用原圖高 */
    public int $maxHeight = 0;

    /** 寬高都未設時，長邊上限 */
    public int $maxEdge = 1920;

    /** 僅 pad_fill 使用 */
    public string $padColor = '#FFFFFF';

    public int $jpegQuality = 82;

    public bool $enabled = true;

    public string $disk = 'public';

    public string $storageDirectory = 'media/images';

    /**
     * 上傳、壓縮並儲存。
     *
     * 參數對應：A=files, B=fitMode, C=maxWidth, D=maxHeight
     * B 要略過、C/D 要設：傳 null → upload($files, null, 1025, 900)
     * 或改用 uploadWithOptions($files, ['max_width' => 1025, 'max_height' => 900])
     *
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @return list<array{
     *     path: string,
     *     url: string|null,
     *     original_name: string,
     *     mime_type: string|null,
     *     file_size: int,
     *     extension: string,
     * }>
     */
    public function upload(
        array|UploadedFile|null $files,
        ?string $fitMode = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
    ): array {
        $files = $this->normalizeFiles($files);
        if ($files === []) {
            return [];
        }

        $options = $this->resolveCompressOptions($fitMode, $maxWidth, $maxHeight);
        $saved = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $row = $this->storeOne($file, $options);
            if ($row !== null) {
                $saved[] = $row;
            }
        }

        return $saved;
    }

    /**
     * 只傳要覆寫的選項（推薦：省略中間參數時最好用）。
     *
     * @param  array{
     *     fit_mode?: string,
     *     fitMode?: string,
     *     max_width?: int,
     *     maxWidth?: int,
     *     max_height?: int,
     *     maxHeight?: int,
     * }  $options  沒出現的 key 沿用物件屬性（fitMode / maxWidth / maxHeight）
     * @return list<array<string, mixed>>
     */
    public function uploadWithOptions(array|UploadedFile|null $files, array $options = []): array
    {
        $fitMode = null;
        if (array_key_exists('fit_mode', $options)) {
            $fitMode = $options['fit_mode'];
        } elseif (array_key_exists('fitMode', $options)) {
            $fitMode = $options['fitMode'];
        }

        $maxWidth = null;
        if (array_key_exists('max_width', $options)) {
            $maxWidth = (int) $options['max_width'];
        } elseif (array_key_exists('maxWidth', $options)) {
            $maxWidth = (int) $options['maxWidth'];
        }

        $maxHeight = null;
        if (array_key_exists('max_height', $options)) {
            $maxHeight = (int) $options['max_height'];
        } elseif (array_key_exists('maxHeight', $options)) {
            $maxHeight = (int) $options['maxHeight'];
        }

        return $this->upload($files, $fitMode, $maxWidth, $maxHeight);
    }

    /**
     * 從原生 $_FILES 上傳（非 Laravel Request 時）。
     *
     * @return list<array<string, mixed>>
     */
    public function uploadFromGlobalFiles(
        string $inputName = 'images',
        ?string $fitMode = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null,
    ): array {
        if (! isset($_FILES[$inputName])) {
            return [];
        }

        return $this->upload(
            $this->filesFromGlobal($inputName),
            $fitMode,
            $maxWidth,
            $maxHeight,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    protected function storeOne(UploadedFile $file, array $options): ?array
    {
        $originalName = basename($file->getClientOriginalName());
        $directory = trim($this->storageDirectory, '/');

        $result = ImageCompressor::compress($file, $options);

        if ($result !== null) {
            $filename = $this->uniqueFilename($result['extension']);
            $path = $directory . '/' . $filename;
            Storage::disk($this->disk)->put($path, $result['contents']);

            return $this->formatStoredFile($path, $originalName, $result['mime_type'], strlen($result['contents']), $result['extension']);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = $this->uniqueFilename($extension ?: 'bin');
        $path = $file->storeAs($directory, $filename, $this->disk);
        if ($path === false) {
            return null;
        }

        return $this->formatStoredFile(
            $path,
            $originalName,
            $file->getMimeType(),
            (int) (Storage::disk($this->disk)->size($path) ?: $file->getSize()),
            $extension
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatStoredFile(string $path, string $originalName, ?string $mime, int $fileSize, string $extension): array
    {
        $url = $this->disk === 'public' ? Storage::disk('public')->url($path) : null;

        return [
            'path' => $path,
            'url' => $url,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'file_size' => $fileSize,
            'extension' => $extension,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveCompressOptions(?string $fitMode, ?int $maxWidth, ?int $maxHeight): array
    {
        return [
            'enabled' => $this->enabled,
            'max_width' => $maxWidth ?? $this->maxWidth,
            'max_height' => $maxHeight ?? $this->maxHeight,
            'max_edge' => $this->maxEdge,
            'fit_mode' => ImageCompressor::normalizeFitMode($fitMode ?? $this->fitMode),
            'pad_color' => $this->padColor,
            'jpeg_quality' => $this->jpegQuality,
        ];
    }

    protected function uniqueFilename(string $extension): string
    {
        $extension = ltrim(strtolower($extension), '.');

        return date('YmdHis') . '-' . Str::uuid()->toString() . ($extension !== '' ? '.' . $extension : '');
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @return list<UploadedFile>
     */
    protected function normalizeFiles(array|UploadedFile|null $files): array
    {
        if ($files === null) {
            return [];
        }
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile));
    }

    /**
     * @return list<UploadedFile>
     */
    protected function filesFromGlobal(string $inputName): array
    {
        $bag = $_FILES[$inputName];
        if (! is_array($bag['name'] ?? null)) {
            if (($bag['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }

            return [$this->makeUploadedFileFromGlobal($bag)];
        }

        $files = [];
        foreach ($bag['name'] as $i => $name) {
            if (($bag['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = $this->makeUploadedFileFromGlobal([
                'name' => $name,
                'type' => $bag['type'][$i] ?? null,
                'tmp_name' => $bag['tmp_name'][$i] ?? '',
                'error' => $bag['error'][$i] ?? UPLOAD_ERR_OK,
                'size' => $bag['size'][$i] ?? 0,
            ]);
        }

        return $files;
    }

    /**
     * @param  array<string, mixed>  $file
     */
    protected function makeUploadedFileFromGlobal(array $file): UploadedFile
    {
        return new UploadedFile(
            (string) ($file['tmp_name'] ?? ''),
            (string) ($file['name'] ?? 'file'),
            (string) ($file['type'] ?? null),
            (int) ($file['error'] ?? UPLOAD_ERR_OK),
            true
        );
    }
}
