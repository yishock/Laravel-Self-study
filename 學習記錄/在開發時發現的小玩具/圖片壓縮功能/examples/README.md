# 範例索引

```
前台 images[] → MediaUploadController → ImageUploadService::upload() → Storage
```

| 檔案 | 說明 |
|------|------|
| `MediaUploadController.example.php` | C 層 |
| `upload-form.blade.example.php` | 表單 |
| `register-service.example.php` | DI 註冊 |

```php
$saved = $this->imageUpload->upload($request->file('images', []), 'stretch', 1025, 900);
```
