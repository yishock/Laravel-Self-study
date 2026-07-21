<?php

/**
 * 路由範例（多媒體上傳，與 Order 無關）
 */

use App\Http\Controllers\Example\MediaUploadController;

Route::post('media/upload', [MediaUploadController::class, 'storeInline']);
Route::post('media/upload-props', [MediaUploadController::class, 'storeWithProperties']);
