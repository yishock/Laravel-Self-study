<?php
/**
 * 2026/03/26 by.YS 註記說明完整流程
 * 此為範例檔
 * 如果複製此檔案坐實作時需要把相關引入的路徑做調整
 * 就是下方 use 的部分
 * 如果你要使用這個範例檔
 * 需要把檔名修改把 .example 移除
 * 並複製到 app\Http\Controllers\ 下即可直接使用
 * 
 * 關聯模組為
 * app\Imports\ExcelReader.php 
 * 一定要確保此模組存在並未被客製化調整過
 * 
 * */
namespace App\Http\Controllers;

use App\Imports\ExcelReader;    // 必要
use App\Models\Order;           // 範例 假的 Model 請忽略，只是使用範例
use App\Models\OrderDetail;     // 範例 假的 Model 請忽略，只是使用範例
use App\Models\Vendor;          // 範例 假的 Model 請忽略，只是使用範例
use Illuminate\Http\Request;    // 必要

/**
 * 
 * Excel匯入資料的執行範例
 * 
 */
class ImportController extends Controller
{
    // 顯示上傳頁面
    public function index()
    {
        return view('import.index');
    }

    // 上傳檔案匯入
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:20480',
        ]);

        return $this->processImport($request->file('file'));
    }

    // 本機檔案匯入
    public function importLocal()
    {
        $path = ExcelReader::localPath('data.xlsx');

        abort_if(!file_exists($path), 404, '找不到本機檔案，請確認 storage/app/imports/data.xlsx');

        return $this->processImport($path);
    }

    // 共用匯入流程
    private function processImport(mixed $file)
    {
        // 取得全部 工作表名稱
        $sheetNames = ExcelReader::getSheetNames($file);
        $errors     = [];

        if (count($sheetNames) === 1) {
            $rows   = ExcelReader::read($file);
            $errors = $this->handleSheet($sheetNames[0], $rows);
        } else {
            /**
             * 
             * 或者指定工作表做處理
             * ExcelReader::readAll($file) 換成 
             * ExcelReader::readSheets($file, ['廠商', '訂單', '訂單明細']);
             * 
             * */
            foreach (ExcelReader::readAll($file) as $sheetName => $rows) {
                $errors = array_merge($errors, $this->handleSheet($sheetName, $rows));
            }
        }

        if (!empty($errors)) {
            return back()->with('errors', $errors);
        }

        return back()->with('success', '匯入成功');
    }

    // 依工作表名稱分派對應處理
    private function handleSheet(string $sheetName, $rows): array
    {
        return match($sheetName) {
            'vendors', '廠商'     => $this->handleVendors($rows),
            'orders',  '訂單'     => $this->handleOrders($rows),
            'details', '訂單明細' => $this->handleOrderDetails($rows),
            default               => $this->skipUnknownSheet($sheetName),
        };
    }

    // 紀錄 不存在的資料表
    private function skipUnknownSheet(string $sheetName): array
    {
        \Log::info("ImportController：略過不認識的工作表「{$sheetName}」");
        return [];
    }

    // 廠商匯入邏輯
    private function handleVendors($rows): array
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            // 驗證
            if (empty($row['廠商編號'])) {
                $errors[] = "第{$line}行：廠商編號不得為空";
                continue;
            }
            if (empty($row['廠商名稱'])) {
                $errors[] = "第{$line}行：廠商名稱不得為空";
                continue;
            }

            // 寫入
            Vendor::updateOrCreate(
                ['vendor_no' => trim($row['廠商編號'])],
                [
                    'name'  => trim($row['廠商名稱'] ?? ''),
                    'phone' => trim($row['電話']     ?? ''),
                ]
            );
        }

        return $errors;
    }

    // 訂單匯入邏輯
    private function handleOrders($rows): array
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            if (empty($row['order_no'])) {
                $errors[] = "第{$line}行：訂單編號不得為空";
                continue;
            }

            Order::updateOrCreate(
                ['order_no' => trim($row['order_no'])],
                [
                    'customer_name' => trim($row['customer_name'] ?? ''),
                    'total'         => $row['total'] ?? 0,
                ]
            );
        }

        return $errors;
    }

    // 訂單明細匯入邏輯（需先有訂單）
    private function handleOrderDetails($rows): array
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            if (empty($row['order_no'])) {
                $errors[] = "第{$line}行：訂單編號不得為空";
                continue;
            }

            // 找對應訂單
            $order = Order::where('order_no', trim($row['order_no']))->first();

            if (!$order) {
                $errors[] = "第{$line}行：找不到對應訂單 {$row['order_no']}";
                continue;
            }

            OrderDetail::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'product'  => trim($row['product'] ?? ''),
                ],
                [
                    'qty'   => $row['qty']   ?? 0,
                    'price' => $row['price'] ?? 0,
                ]
            );
        }

        return $errors;
    }
}
