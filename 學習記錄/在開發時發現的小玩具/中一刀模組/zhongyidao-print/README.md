# zhongyidao-print（中一刀列印模組）

可獨立上傳 Git、複製到其他專案的中一刀 HTML/CSS 列印資源包。  
**不含**任何特定 ERP 路徑或框架程式碼。

## 目錄結構

```
zhongyidao-print/
├── README.md                 ← 本檔
├── docs/
│   └── SPEC.md               ← 紙張、印表機、模組代號規格
├── assets/css/
│   ├── paper-variables.css   ← 列印範圍 215.9×139.7mm、留白變數
│   ├── type-a-overlay.css    ← 版型 A：套印／全印（196×140 表單框）
│   └── type-b-shipment.css   ← 版型 B：產品出貨單完整樣式
├── templates/
│   ├── type-a/overlay-form.skeleton.html
│   └── type-b/shipment-order.page.html   ← {{佔位符}} 單頁骨架
├── lib/php/
│   ├── ShipmentPrintLines.php            ← 出貨單明細拆行、分頁
│   └── OverlayFormPagination.php         ← 套印單據表身分頁
├── examples/
│   ├── preview-type-a.html               ← 瀏覽器預覽版型 A
│   ├── preview-type-b.html               ← 瀏覽器預覽版型 B
│   └── data-shipment-sample.json         ← 範例資料結構
└── integrations/
    └── laravel/README.md                 ← Laravel 接入說明
```

## 快速開始

1. 印表機建立自訂紙 **中一刀**：寬 21.59 cm、高 13.97 cm，邊界 0（詳見 `docs/SPEC.md`）。
2. 用瀏覽器開啟 `examples/preview-type-b.html`，Ctrl+P 試印。
3. 新專案複製 `assets/css` + 需要的 `templates` + `lib/php`。
4. 後端把資料填入模板；佔位符見 `templates/type-b/shipment-order.page.html`。

## 模組代號（業務）

| 代號 | 版型 | CSS |
|------|------|-----|
| `sales-invoice`、`purchase-order`… | A 套印／全印 | `type-a-overlay.css` |
| `shipment-order` 產品出貨單 | B 四聯自排版 | `paper-variables.css` + `type-b-shipment.css` |

完整清單見 `docs/SPEC.md` §4。

## 佔位符約定

模板內使用 `{{field_name}}` 表示由後端替換。  
迴圈區塊以註解標示：

```html
<!-- REPEAT: item-row -->
...
<!-- END REPEAT -->
```

## PHP 工具（無 Composer 依賴）

```php
require 'lib/php/ShipmentPrintLines.php';

$lines = ShipmentPrintLines::build($items, 0.0, fn ($item) => $item['name']);
$pages = ShipmentPrintLines::chunkPages($lines);
// $pages[0] 最多 8 列，見 ROWS_PER_PAGE
```

```php
require 'lib/php/OverlayFormPagination.php';

$pages = OverlayFormPagination::paginate(['產品名稱一', '很長的產品名稱二…']);
```

## 上傳 Git

在本資料夾初始化倉庫即可：

```bash
cd zhongyidao-print
git init
git add .
git commit -m "feat: 中一刀列印模組（CSS、模板、規格）"
git remote add origin <你的-repo-url>
git push -u origin main
```

## 修訂

| 日期 | 說明 |
|------|------|
| 2026-06-02 | 初版：自 ERP 抽出版型 B CSS；紙張改 215.9mm；附模板與 PHP 分頁 |
