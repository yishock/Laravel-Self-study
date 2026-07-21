# 圖片壓縮擴充模組

> **獨立擴充**：上傳 → 壓縮 → 儲存（多媒體管理用，與 Order 無關）

---

## 兩層結構

| 類別 | 角色 |
|------|------|
| **`ImageUploadService`** | C 層呼叫：`upload()` |
| **`ImageCompressor`** | 底層 GD 引擎 |

---

## C 層用法

### 先設屬性，再 upload

```php
$this->imageUpload->fitMode = 'stretch';
$this->imageUpload->maxWidth = 1025;
$this->imageUpload->maxHeight = 900;
$this->imageUpload->storageDirectory = 'media/library';

$saved = $this->imageUpload->upload($request->file('images', []));
```

### 一次傳參

```php
$saved = $this->imageUpload->upload(
    $request->file('images', []),
    'pad_transparent',
    1025,
    900
);
```

### 原生 $_FILES

```php
$saved = $this->imageUpload->uploadFromGlobalFiles('images', 'contain', 800, 0);
```

---

## 參數省略（B 用預設，C/D 要設）

`upload($files, $fitMode, $maxWidth, $maxHeight)` 對應 **A, B, C, D**。

**傳 `null` = 該參數不覆寫，改用物件屬性預設**（`$this->fitMode` 等）。

### 方式 1：中間傳 null（最直覺）

```php
// B(fitMode) 用預設 stretch，只設寬高
$saved = $this->imageUpload->upload($files, null, 1025, 900);
```

### 方式 2：PHP 8 命名參數（可跳順序）

```php
$saved = $this->imageUpload->upload(
    files: $files,
    maxWidth: 1025,
    maxHeight: 900,
);
```

### 方式 3：先設屬性，只傳 files

```php
$this->imageUpload->maxWidth = 1025;
$this->imageUpload->maxHeight = 900;
// fitMode 維持 register 時的預設
$saved = $this->imageUpload->upload($files);
```

### 方式 4：uploadWithOptions（推薦維護）

```php
$saved = $this->imageUpload->uploadWithOptions($files, [
    'max_width'  => 1025,
    'max_height' => 900,
    // 沒寫 fit_mode → 用預設
]);
```

| 需求 | 寫法 |
|------|------|
| 全部預設 | `upload($files)` |
| 只改 fitMode | `upload($files, 'contain')` |
| 跳 fitMode，改寬高 | `upload($files, null, 1025, 900)` |
| 只改寬 | `upload($files, null, 800, null)` 或 `uploadWithOptions($files, ['max_width' => 800])` |

---

## 可調屬性

| 屬性 | 預設 | 說明 |
|------|------|------|
| `fitMode` | `stretch` | 縮放模式 |
| `maxWidth` | `0` | 寬；0 = 原圖寬 |
| `maxHeight` | `0` | 高；0 = 原圖高 |
| `maxEdge` | `1920` | 寬高都 0 時長邊上限 |
| `padColor` | `#FFFFFF` | 僅 `pad_fill` |
| `jpegQuality` | `82` | 品質 |
| `storageDirectory` | `media/images` | 存檔目錄 |
| `disk` | `public` | Storage disk |

---

## fitMode 值

`stretch` · `contain` · `cover` · `pad_transparent` · `pad_fill`

---

## 目錄

```
src/
├── ImageUploadService.php   ← C 層用
└── ImageCompressor.php      ← 底層
examples/
├── MediaUploadController.example.php
├── upload-form.blade.example.php
└── register-service.example.php
```

---

*紀錄建立：2026-07-21*
