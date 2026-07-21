<?php

namespace App\Http\Controllers\Example;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ReferenceModules\ImageCompress\ImageUploadService;

class MediaUploadController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageUpload,
    ) {}

    /** 先設屬性再 upload */
    public function storeWithProperties(Request $request): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $this->imageUpload->fitMode = 'pad_transparent';
        $this->imageUpload->maxWidth = 1025;
        $this->imageUpload->maxHeight = 900;
        $this->imageUpload->storageDirectory = 'media/library';

        return response()->json([
            'files' => $this->imageUpload->upload($request->file('images', [])),
        ]);
    }

    /** upload 一次傳 fitMode / 寬 / 高 */
    public function storeInline(Request $request): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $saved = $this->imageUpload->upload(
            $request->file('images', []),
            'stretch',
            1025,
            900
        );

        return response()->json(['files' => $saved]);
    }
}
