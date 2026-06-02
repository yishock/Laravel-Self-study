# Laravel 接入指引

本資料夾僅說明如何引用 **zhongyidao-print** 套件內容，不包含專案專屬路徑。

## 1. 複製檔案

| 套件內 | 建議放到 Laravel 專案 |
|--------|------------------------|
| `assets/css/*` | `public/vendor/zhongyidao-print/css/` |
| `lib/php/ShipmentPrintLines.php` | `app/Support/ShipmentPrintLines.php`（或保留類名、改 namespace） |
| `lib/php/OverlayFormPagination.php` | `app/Support/OverlayFormPagination.php` |
| `templates/type-b/shipment-order.page.html` | 轉成 `resources/views/prints/shipment-order.blade.php` |
| `templates/type-a/*.html` | 轉成對應 Blade |

## 2. Blade 範例（版型 B 一頁）

```blade
<link rel="stylesheet" href="{{ asset('vendor/zhongyidao-print/css/paper-variables.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/zhongyidao-print/css/type-b-shipment.css') }}">

@php
    use App\Support\ShipmentPrintLines;
    $lines = ShipmentPrintLines::build($items, $shippingFee, fn ($i) => $i->name);
    $pages = ShipmentPrintLines::chunkPages($lines);
@endphp

@foreach ($pages as $pageIndex => $pageLines)
    {{-- 引入 templates/type-b 結構，@for ($row = 0; $row < 8; $row++) 填列 --}}
@endforeach
```

## 3. 列印路由

- 回傳 HTML view，`target="_blank"` 開啟
- 頁尾可加 `<script>window.print();</script>`（選用）

## 4. 注意

- `@page` 已設 **215.9×139.7mm**，與印表機自訂紙「中一刀」一致
- 勿再把洞區加進 `--paper-w`
- 實印後只在本專案記錄校正過的 `--pad-*` 數值（可寫 env 或 config）
