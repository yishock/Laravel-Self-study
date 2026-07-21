# API 說明

---

## ImageUploadService（C 層入口）

```php
$saved = $imageUpload->upload($files);
$saved = $imageUpload->upload($files, $fitMode, $maxWidth, $maxHeight);
$saved = $imageUpload->upload($files, null, 1025, 900);  // 跳過 fitMode
$saved = $imageUpload->uploadWithOptions($files, ['max_width' => 1025, 'max_height' => 900]);
$saved = $imageUpload->uploadFromGlobalFiles('images', $fitMode, $maxWidth, $maxHeight);
```

**`null` = 該參數不覆寫，沿用物件屬性預設。**

### 回傳每筆

```php
[
    'path' => 'media/images/20260721150000-uuid.jpg',
    'url' => '/storage/media/images/...',
    'original_name' => 'photo.jpg',
    'mime_type' => 'image/jpeg',
    'file_size' => 123456,
    'extension' => 'jpg',
]
```

### 公開屬性

| 屬性 | 說明 |
|------|------|
| `fitMode` | stretch / contain / cover / pad_transparent / pad_fill |
| `maxWidth` | 目標寬 px |
| `maxHeight` | 目標高 px |
| `maxEdge` | 長邊上限 |
| `padColor` | pad_fill 填色 |
| `jpegQuality` | 1–100 |
| `enabled` | 是否壓縮 |
| `disk` | Storage disk |
| `storageDirectory` | 子目錄 |

---

## ImageCompressor（底層）

直接呼叫時使用 `compress($file, $options)`，一般 C 層用 `ImageUploadService` 即可。

---

## fitMode 對照

| fitMode | 行為 |
|---------|------|
| `stretch` | 硬拉至 maxWidth × maxHeight |
| `contain` | 等比塞進框 |
| `cover` | 等比裁切填滿 |
| `pad_transparent` | 等比置中，透明底 |
| `pad_fill` | 等比置中，padColor 填色 |

寬或高只設一個即可；未設者沿用原圖尺寸。
