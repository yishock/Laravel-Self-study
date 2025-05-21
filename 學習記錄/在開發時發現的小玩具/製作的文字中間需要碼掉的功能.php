<?php
// 2024/08/22 
// 製作個 字串中間 能替換成其他東西的小工具 by.bom
/* 功能目的
    因應需求 需要把 手機號碼抓間數字碼成*
    接收資料說明
    $str => "text" 目標字串
    $replace => "*" 想用什麼東西做碼掉的字 預設 *
    $leftLen => 3 顯示字串前X字 預設 3 希望這個是一定要填拉
    $rightLen => 3 顯示字串最後X字 預設 3
    $type => 1 預設 1
        1 : 只碼中間
        2 : 只碼後面
        3 : 只碼前面
    $replaceLen => 0 預設0 如果有丟值的話 會依你希望  $replace 出現幾個 不然會依被碼掉的字串量

    其餘條件是
    如果字的數量 < ($leftLen + $rightLen) 就只會跑 $type : 2 or 3 預設會跑 2
*/
if (!function_exists('CoverText')) {
    function CoverText($str , $replace="*" , $leftLen=3 , $rightLen=3 , $type=1 , $replaceLen=0 ) {
        if(empty($str)){
            return $str;
        }
        $length = mb_strlen($str,"utf-8");
        $type = ($type==1)?(($length<=($leftLen+$rightLen))?2:$type):$type;
        $re_str = $str;
        switch ($type) {
            case 1:
                $leftText = mb_substr( $str,0,$leftLen,"utf-8");
                $rightText = mb_substr( $str,-$rightLen,$rightLen,"utf-8");

                $replaceLen = !empty($replaceLen)? $replaceLen : $length-($leftLen + $rightLen );
                $repeatText = str_repeat("{$replace}", $replaceLen );

                $re_str = $leftText . $repeatText . $rightText;
                break;
            case 2:
                $leftText = mb_substr( $str,0,$leftLen,"utf-8");

                $replaceLen = !empty($replaceLen)? $replaceLen : $length-$leftLen;
                $repeatText = str_repeat("{$replace}", $replaceLen );

                $re_str = $leftText . $repeatText;
                break;
            case 3:
               $rightText = mb_substr( $str,-$rightLen,$rightLen,"utf-8");

               $replaceLen = !empty($replaceLen)? $replaceLen : $length-$rightLen;
               $repeatText = str_repeat("{$replace}", $replaceLen );

               $re_str = $repeatText . $rightText;
                break;
        }
        return $re_str;
    }
}
