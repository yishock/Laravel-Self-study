<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\IOFactory; // 非必要 這是為了 內部用：分批讀取 做安裝的套件

// 內部用：單一工作表讀取
class SingleSheetReader implements ToCollection, WithMultipleSheets
{
    public Collection $rows;
    private string|int $sheet; // 可以是工作表名稱或索引（0開始）

    public function __construct(string|int $sheet = 0)
    {
        $this->rows  = collect();
        $this->sheet = $sheet;
    }

    // 告訴 Maatwebsite 只處理這一張
    public function sheets(): array
    {
        return [$this->sheet => $this];
    }

    public function collection(Collection $rows)
    {
        $header = $rows->shift()->map(fn($v) => trim((string)$v))->toArray();

        $this->rows = $rows
            ->filter(fn($row) =>
                collect($row)->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')->isNotEmpty()
            )
            ->map(function ($row) use ($header) {
                $mapped = [];
                foreach ($header as $index => $key) {
                    $mapped[$key] = $row[$index] ?? null;
                }
                return $mapped;
            })
            ->values();
    }
}

// 內部用：分批讀取
// 目前是為規劃做使用 是請GPT處理檔案過大時的處理方案，但目前未需求(10萬筆以上再做考慮)
class ChunkSheetReader implements ToCollection, WithChunkReading
{
    // 同樣移除 WithHeadingRow
    public Collection $rows;
    private int $chunkSize;
    private bool $isFirstChunk = true;
    private array $header      = [];

    public function __construct(int $chunkSize = 500)
    {
        $this->rows      = collect();
        $this->chunkSize = $chunkSize;
    }

    public function collection(Collection $rows)
    {
        // 第一個 chunk 的第一列是表頭
        if ($this->isFirstChunk) {
            $this->header       = $rows->shift()->map(fn($v) => trim((string)$v))->toArray();
            $this->isFirstChunk = false;
        }

        $header   = $this->header;
        $filtered = $rows
            ->filter(function ($row) {
                return collect($row)->filter(fn($v) => !is_null($v) && trim((string)$v) !== '')->isNotEmpty();
            })
            ->map(function ($row) use ($header) {
                $mapped = [];
                foreach ($header as $index => $key) {
                    $mapped[$key] = $row[$index] ?? null;
                }
                return $mapped;
            })
            ->values();

        $this->rows = $this->rows->merge($filtered);
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }
}

// 通用 ExcelReader
class ExcelReader
{
    /**
     * 單一工作表讀取
     * $file 可以是上傳的 UploadedFile 或本機路徑字串
     */
    public static function read(mixed $file, string|int $sheet = 0): Collection
    {
        $reader = new SingleSheetReader($sheet);
        Excel::import($reader, $file);
        return $reader->rows;
    }

    /**
     * 多工作表讀取（指定工作表名稱）
     * 回傳 ['工作表名稱' => Collection, ...]
     * 
     * 請GPT處理多工作表，並指定工作表來處理
     * 看了目前專案有很多有不必使用到的目錄表之類的範例文件
     * 為了那些文件做處理，不然使用readAll()來取得全部工作表即可
     */
    public static function readSheets(mixed $file, array $sheetNames): array
    {

        if (empty($sheetNames)) {
            return [];
        }
        
        $readers = [];

        foreach ($sheetNames as $name) {
            $readers[$name] = new SingleSheetReader();
        }

        $import = new class($readers) implements WithMultipleSheets {
            public function __construct(private array $readers) {}
            public function sheets(): array { return $this->readers; }
        };

        Excel::import($import, $file);

        $results = [];
        foreach ($readers as $name => $reader) {
            $results[$name] = $reader->rows;
        }

        return $results;
    }

    /**
     * 自動偵測所有工作表並讀取
     * 回傳 ['工作表名稱' => Collection, ...]
     */
    public static function readAll(mixed $file): array
    {
        $sheetNames = self::getSheetNames($file);
        return self::readSheets($file, $sheetNames);
    }

    /**
     * 大量資料分批讀取（單一工作表）
     * 目前是為規劃做使用 是請GPT處理檔案過大時的處理方案，但目前未需求(10萬筆以上再做考慮)
     */
    public static function readChunk(mixed $file, int $chunkSize = 500): Collection
    {
        $reader = new ChunkSheetReader($chunkSize);
        Excel::import($reader, $file);
        return $reader->rows;
    }

    /**
     * 取得所有工作表名稱
     */
    public static function getSheetNames(mixed $file): array
    {
        $path        = is_string($file) ? $file : $file->getRealPath();
        $spreadsheet = IOFactory::load($path);
        return $spreadsheet->getSheetNames();
    }

    /**
     * 取得本機檔案路徑
     * 預設放在 storage/app/imports/
     * 次資料夾的檔案理論上是不會上傳至git如果更新時發現有檔案上傳的話
     * 麻煩至 .gitignore 設定 或 storage\app\.gitignore 是否有被調整有排除"imports"資料夾
     */
    public static function localPath(string $filename): string
    {
        return storage_path('app/imports/' . $filename);
    }
}
