# 後台列表欄位個人化 — 搬遷與擴充教學

## 快速摘要

本套件支援兩種儲存方式，並可用切換開關讓使用者自行選擇：

| 模式 | 說明 | 需要 DB |
|------|------|---------|
| **本機暫存** | 設定存在瀏覽器 `localStorage`，只在此電腦／此瀏覽器有效 | 否 |
| **帳號同步** | 設定存入資料庫，登入同一帳號換電腦也會帶著走 | 是 |

切換開關的全域偏好本身也記在本機 `localStorage`（`backend_list_prefs:storage_mode`）。

---

## 方案 A：完整版（帳號同步 + 本機暫存 + 切換開關）

### 全專案一次：6 個檔案放固定資料夾 + 1 個檔案調整

| # | 複製來源 | 放到 |
|---|----------|------|
| 1 | `app/Models/AdminUserListPreference.php` | `app/Models/` |
| 2 | `app/Http/Controllers/Backend/AdminUserListPreferenceController.php` | `app/Http/Controllers/Backend/` |
| 3 | `database/migrations/…_create_admin_user_list_preferences_table.php` | `database/migrations/` |
| 4 | `public/css/backend-list-preferences.css` | `public/css/` |
| 5 | `public/js/backend-list-preferences-storage.js` | `public/js/` |
| 6 | `public/js/backend-list-preferences.js` | `public/js/` |
| 7 | `public/js/backend-form-table-preferences.js`（表單明細用，可選） | `public/js/` |

**1 個檔案調整：** `routes/backend.php` 加 3 行 API 路由（見 `routes/backend.snippet.php`）

**資料表：** 執行 `php artisan migrate`，新增 `admin_user_list_preferences` 空表即可。

---

## 方案 B：輕量版（僅本機暫存，不用 DB）

若專案不需要跨裝置同步，只要瀏覽器記住欄位設定：

| # | 複製來源 | 放到 |
|---|----------|------|
| 1 | `public/css/backend-list-preferences.css` | `public/css/` |
| 2 | `public/js/backend-list-preferences-storage.js` | `public/js/` |
| 3 | `public/js/backend-list-preferences.js` | `public/js/` |

**不需要：** Model、Controller、Migration、API 路由。

初始化時指定：

```javascript
BackendListPreferences.init({
    pageKey: 'companies.index',
    storageMode: 'local',       // 固定本機暫存
    showStorageToggle: false,   // 不顯示切換開關
    // apiBaseUrl 可省略
    ...
});
```

---

## 每個列表頁：改 `index.blade.php`

### ① 表格上方加 HTML（切換開關 + 恢復按鈕）

放在 `<table id="data-table">` 上方：

```html
<div class="list-prefs-toolbar">
    <label class="list-prefs-storage-toggle" title="切換欄位設定儲存方式">
        <span class="list-prefs-storage-label" data-storage-mode="local">本機暫存</span>
        <span class="list-prefs-switch">
            <input type="checkbox" id="list-prefs-storage-mode" aria-label="切換本機暫存或帳號同步">
            <span class="list-prefs-switch-slider"></span>
        </span>
        <span class="list-prefs-storage-label" data-storage-mode="server">帳號同步</span>
    </label>
    <button type="button" class="btn btn-link text-muted" id="btn-reset-list-prefs">恢復列表預設配置</button>
</div>
```

- 開關**關閉**（左）＝ 本機暫存
- 開關**開啟**（右）＝ 帳號同步（需 DB + API）

若使用輕量版，可省略切換開關區塊，只留恢復按鈕。

### ② 引入 JS / CSS

- `@push('style')` 引入 `backend-list-preferences.css`
- `@push('scripts')` 先引入 `backend-list-preferences-storage.js`，再引入 `backend-list-preferences.js`
- 用 `BackendListPreferences.init({...})` 取代 `DataTable()`
- 欄位改寫成 `columnDefs` 陣列（每欄建議加 `name`）

### ③ 每頁必改：`pageKey`（頁面金鑰）

全專案唯一，一頁一個金鑰：

```javascript
var pageKey = 'companies.index';  // 依模組命名
```

| 頁面 | pageKey 範例 |
|------|----------------|
| 公司別 | `companies.index` |
| 客戶 | `customer.index` |
| 員工 | `staff.index` |
| 產品 | `product.index` |

### ④ 列表 DataTable 調整範例

完整範本（以公司別為例，欄位請換成該頁自己的 `columnDefs`）：

```blade
@push('style')
<link rel="stylesheet" href="{{ asset('css/backend-list-preferences.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('js/backend-list-preferences-storage.js') }}"></script>
<script src="{{ asset('js/backend-list-preferences.js') }}"></script>
<script type="text/javascript">
    (async function () {
        var path = '{{ route('backend.'.$routeNameData.'.index') }}';
        var tableList = $('#data-table');
        var listPrefsApiUrl = '{{ url('/backend/list-preferences') }}';
        var pageKey = 'companies.index';  // ← 每頁改成對應金鑰

        var columnDefs = [
            {
                name: 'row_index',
                data: 'id',
                className: 'text-md-center',
                title: '#',
                orderable: false,
                searchable: false,
                width: '50px',
                render: function (data, type, full, meta) {
                    return meta.row + 1 + meta.settings._iDisplayStart;
                },
            },
            // ... 其餘欄位照原本 columns 改寫，每欄建議加 name ...
        ];

        var table = await BackendListPreferences.init({
            pageKey: pageKey,
            apiBaseUrl: listPrefsApiUrl,
            showStorageToggle: true,
            storageToggleSelector: '#list-prefs-storage-mode',
            tableElement: tableList,
            columnDefs: columnDefs,
            showResetButton: true,
            resetButtonSelector: '#btn-reset-list-prefs',
            dataTableOptions: {
                autoWidth: false,
                processing: false,
                serverSide: false,
                responsive: false,
                ajax: path,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                pageLength: 10,
                order: [[1, 'asc']],
                language: { url: '{{ asset('zh-Hant.json') }}' },
                stateSave: false,  // 必須 false
                drawCallback: function () {
                    Codebase.helpers('magnific-popup');
                },
            },
        });

        // 刪除等事件綁定維持不變，table 變數仍可用
        tableList.on('click', '.delete', function () {
            var id = $(this).data('id');
            Swal.fire({
                text: '{{ __('confirm_delete') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('sure') }}',
                cancelButtonText: '{{ __('cancel') }}',
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `${path}/${id}`,
                        type: 'DELETE',
                        dataType: 'json',
                        success: function (data) {
                            Swal.fire({ text: data.message, icon: 'success' }).then(function () {
                                table.ajax.reload(null, false);
                            });
                        },
                    });
                }
            });
        });
    })();
</script>
@endpush
```

---

## init 選項（儲存相關）

| 選項 | 說明 | 預設 |
|------|------|------|
| `apiBaseUrl` | 帳號同步 API 根路徑 | — |
| `storageMode` | 強制 `'local'` 或 `'server'`，不讀切換開關 | 讀取切換開關 |
| `defaultStorageMode` | 使用者第一次進來、尚未切換過時的預設 | `'server'` |
| `showStorageToggle` | 是否綁定切換開關 | `false` |
| `storageToggleSelector` | 切換開關 input 選擇器 | `#list-prefs-storage-mode` |

表單明細 `BackendFormTablePreferences.init` 也支援相同選項。
