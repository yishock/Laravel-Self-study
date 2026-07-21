<?php

/**
 * Laravel 服務註冊範例 — 貼到 AppServiceProvider::register()
 *
 * 用途：向「服務容器」登記 ImageUploadService 如何建立、預設值為何。
 *
 * 與 Controller 的對應（靠「類別名稱」，不是變數名）：
 *
 *   register 這裡：singleton(ImageUploadService::class, ...)
 *   Controller：  __construct(protected ImageUploadService $imageUpload)
 *                                      ^^^^^^^^^^^^^^^^^^^
 *   容器看到需要 ImageUploadService → 回傳下面 factory 建立的實例。
 *   $imageUpload 只是 Controller 內的變數名，可改名，不影響對應。
 *
 * namespace 須與 Controller 的 use 一致（複製 class 到 app/ 後改為 App\Services\...）。
 */

use App\Services\ImageCompress\ImageUploadService;

$this->app->singleton(ImageUploadService::class, function () {
    $service = new ImageUploadService();
    $service->disk = 'public';
    $service->storageDirectory = 'media/images';
    $service->fitMode = 'stretch';
    $service->maxEdge = 1920;
    $service->jpegQuality = 82;

    return $service;
});

// Controller 建構子注入（變數名 imageUpload 可自訂）：
// public function __construct(protected ImageUploadService $imageUpload) {}
