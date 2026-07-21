<?php

/**
 * 圖片壓縮設定範例
 *
 * fit_mode 含五種：stretch | contain | cover | pad_transparent | pad_fill
 */

return [

    'fixed_stretch' => [
        'storage_directory' => 'attachments/thumbs',
        'image_compress_enabled' => true,
        'image_max_width' => 300,
        'image_max_height' => 400,
        'image_fit_mode' => 'stretch',
        'image_jpeg_quality' => 82,
    ],

    /*
    | pad_transparent：透明底，無白邊（建議 PNG）
    */
    'fixed_pad_transparent' => [
        'storage_directory' => 'attachments/thumbs',
        'image_compress_enabled' => true,
        'image_max_width' => 300,
        'image_max_height' => 400,
        'image_fit_mode' => 'pad_transparent',
        'image_jpeg_quality' => 82,
    ],

    /*
    | pad_fill：填色底，預設白邊（證件照等）
    */
    'fixed_pad_fill_white' => [
        'storage_directory' => 'attachments/thumbs',
        'image_compress_enabled' => true,
        'image_max_width' => 300,
        'image_max_height' => 400,
        'image_fit_mode' => 'pad_fill',
        'image_pad_color' => '#FFFFFF',
        'image_jpeg_quality' => 82,
    ],

    /*
    | pad_fill：自訂灰底
    */
    'fixed_pad_fill_gray' => [
        'storage_directory' => 'attachments/thumbs',
        'image_compress_enabled' => true,
        'image_max_width' => 300,
        'image_max_height' => 400,
        'image_fit_mode' => 'pad_fill',
        'image_pad_color' => '#F0F0F0',
        'image_jpeg_quality' => 82,
    ],

    'fixed_cover' => [
        'storage_directory' => 'attachments/covers',
        'image_compress_enabled' => true,
        'image_max_width' => 300,
        'image_max_height' => 400,
        'image_fit_mode' => 'cover',
        'image_jpeg_quality' => 85,
    ],

    'fixed_contain' => [
        'storage_directory' => 'attachments/previews',
        'image_compress_enabled' => true,
        'image_max_width' => 300,
        'image_max_height' => 400,
        'image_fit_mode' => 'contain',
        'image_jpeg_quality' => 82,
    ],

    'default' => [
        'storage_directory' => 'attachments/uploads',
        'image_compress_enabled' => true,
        'image_max_width' => 0,
        'image_max_height' => 0,
        'image_max_edge' => 1920,
        'image_fit_mode' => 'stretch',
        'image_jpeg_quality' => 82,
    ],

    'quality_only' => [
        'storage_directory' => 'attachments/other',
        'image_compress_enabled' => true,
        'image_max_width' => 0,
        'image_max_height' => 0,
        'image_max_edge' => 0,
        'image_jpeg_quality' => 75,
    ],

];
