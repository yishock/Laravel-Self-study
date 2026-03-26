# 需要安裝的模組
```
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\IOFactory;
```

# 安裝套件指令
```
composer require maatwebsite/excel
```
# 檢查主套件
```
composer show maatwebsite/excel
```

# 檢查底層依賴
```
composer show phpoffice/phpspreadsheet
```

# 有出現版本資訊就代表已安裝，例如：
```
name     : maatwebsite/excel
descrip. : Supercharged Excel exports and imports in Laravel
versions : * 3.1.48
```

# 一次確認所有相關套件
```
bashcomposer show | grep -E "maatwebsite|phpoffice"
```
