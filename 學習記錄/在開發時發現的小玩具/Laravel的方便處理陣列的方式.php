<?php
/* 這邊在操作 Laravel 的方式 可以丟陣列進去 幫我做排序
範例資料
Array
(
    [Status] => 0
    [Data] => Array
        (
            [0] => Array
                (
                    [ReceiveTime] => 2025-01-08
                    [CompleteTime] => 
                )

            [1] => Array
                (
                    [ReceiveTime] => 2025-01-15
                    [CompleteTime] => 
                )

            [2] => Array
                (
                    [ReceiveTime] => 2025-01-17
                    [CompleteTime] => 
                )

        )

    [Message] => 查詢成功
)

$response = [
    "Status" => 0,
    "Data" => [
        [
            "ReceiveTime" => "2025-01-08", 
            "CompleteTime" => ""
        ],
        [
            "ReceiveTime" => "2025-01-15", 
            "CompleteTime" => ""
        ],
        [
            "ReceiveTime" => "2025-01-17", 
            "CompleteTime" => ""
        ]
    ],
    "Message" => "查詢成功"
];
*/
// 從 小到大
// sortBy 預設
$sortedData = collect($outputJson['Data'])->sortBy('ReceiveTime')->values()->all();
// 從 大到小
$sortedData = collect($outputJson['Data'])->sortByDesc('ReceiveTime')->values()->all();

// values() 作用是 重新編排 key 不然排序完 還是會是舊的 key 可能會影響後續的處理


