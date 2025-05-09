<?php
/*
二維陣列 轉 一維陣列
範例
$records = 
Array(
    [0] => Array(
            [id] => 2135
            [first_name] => John
            [last_name] => Doe
        )
    [1] => Array(
            [id] => 3245
            [first_name] => Sally
            [last_name] => Smith
        )
    [2] => Array(
            [id] => 5342
            [first_name] => Jane
            [last_name] => Jones
        )

    [3] => Array(
            [id] => 5623
            [first_name] => Peter
            [last_name] => Doe
        )
)
*/
$first_names = array_column($records, 'first_name');
/*
Array(
    [0] => John
    [1] => Sally
    [2] => Jane
    [3] => Peter
)
*/

$first_names = array_column($records, 'first_name','id');
/*
Array(
    [2135] => John
    [3245] => Sally
    [5342] => Jane
    [5623] => Peter
)
*/

$first_names= [];
array_walk($records, function($value, $key) use (&$first_names){
    $first_names[] = $value['first_name'];
});
/*
Array(
    [0] => John
    [1] => Sally
    [2] => Jane
    [3] => Peter
)
*/

$first_names= [];
array_map(function($value) use (&$first_names){
    $first_names[] = $value['first_name'];
}, $records);
/*
Array(
    [0] => John
    [1] => Sally
    [2] => Jane
    [3] => Peter
)
*/

$first_names = array_reduce($records,function($result, $value){
    array_push($result, $value['first_name']);
    return $result;
},[]);
/*
Array(
    [0] => John
    [1] => Sally
    [2] => Jane
    [3] => Peter
)
*/

/*
多維陣列 轉為 一維陣列
範例
$user4 = array(    
    'a' => 
        array(
            0 => 100 , 
            1 => 'a1'
        ),    
    'b' => 
        array(
            0 => 101 , 
            1 => 'a2'
        ),    
    'c' => array(        
            'd' => array(
                    0 => 102, 
                    1 => 'a3'
                ),        
            'e' => array(
                    0 => 103, 
                    1 => 'a4'
                ),
        ),
);
*/
$result = [];
array_walk_recursive($user, function($value) use (&$result) {
    array_push($result, $value);
});
/*
$result = 
array(
    [0] => 100
    [1] => 'a1'
    [2] => 101
    [3] => 'a2'
    [4] => 102
    [5] => 'a3'
    [6] => 103
    [7] => 'a4'
)
*/


//陣列尋找key 做移除
function array_remove($data, $key=array()){
    if(!array_key_exists($key, $data)){
        return $data;
    }
    $keys = array_keys($data);
    foreach ($key as $value) {
    	$index = array_search($value, $keys);
    	if($index !== FALSE){
    	    array_splice($data, $index, 1);
    	}
    }
    
    return $data;
}

//陣列 key 做正則判斷
function preg_array_key_exists($pattern, $array) {
    $keys = array_keys($array);    

    return (int) preg_grep($pattern,$keys);
}


//指定 陣列key 來判斷是否有重複
function assoc_unique($arr, $key) {
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if (in_array($v[$key], $tmp_arr)) {
            unset($arr[$k]);
        } else {
            $tmp_arr[] = $v[$key];
        }
    }
    sort($arr);
    return $arr;
}


//一維陣列 重複資料合併  (php 5.2.9 版本後 變可合併二維資料)
array_unique($array);
//二維陣列 重複資料合併
array_unique($array,SORT_REGULAR);

//$array1 的 key 換成 $array2 的value 
array_combine($array1,$array2)

//二維陣列 轉換成 依二維陣列的 key 組出來的 資料
/* 範例：
Array(
    [0] => Array(
            [id] => 1
            [name] => aaa
        )
    [1] => Array(
            [id] => 2
            [name] => bbb
        )
    [2] => Array(
            [id] => 3
            [name] => ccc
        )

    [3] => Array(
            [id] => 4
            [name] => ddd
        )
    [4] => Array(
            [id] => 5
            [name] => eee
        )
)

Array(
    [id] => Array(
            [0] => 1
            [1] => 2
            [2] => 3
            [3] => 4
            [4] => 5
        )
    [name] => Array(
            [0] => aaa
            [1] => bbb
            [2] => ccc
            [3] => ddd
            [4] => eee
        )
)
*/
$array_allocation = array_reduce($array, "array_merge_recursive",[]);

// 使用範例
if(count($array)==1){
  $run_array = array();
  foreach ($array[0] as $key_name => $value_dto) {
    $run_array[$key_name][]=$value_dto;
  }
}else{
  $run_array = array_reduce($array, "array_merge_recursive",[]);
}


//陣列合併
array_merge($array1, $array2, $array3, ..., $arrayN);

//範例
$aaa = array("a","b","c");
$bbb = array("a","a1","a2");
array_merge($aaa,$bbb);
/*
array(
    [0]=>"a",
    [1]=>"b",
    [2]=>"c",
    [3]=>"a",
    [4]=>"a1",
    [5]=>"a2",
);
*/


//陣列 的key 正則判斷
$arr = array("abc"=>12,"dec"=>34,"fgh"=>56);

var_dump(preg_array_key_exists('/c$/',$arr)); // check if a key ends in 'c'.
var_dump(preg_array_key_exists('/x$/',$arr)); // check if a key ends in 'x'.

function preg_array_key_exists($pattern, $array) {
    // extract the keys.
    $keys = array_keys($array);    

    // convert the preg_grep() returned array to int..and return.
    // the ret value of preg_grep() will be an array of values
    // that match the pattern.
    return (int) preg_grep($pattern,$keys);
}

//亂數取直
function generatorPassword($Quantity){
    $password = '';
    $word = 'abcdefghijkmnpqrstuvwxyz!@#$%^&*()-=ABCDEFGHIJKLMNPQRSTUVWXYZ<>;{}[]23456789';
    $len = strlen($word);
    for ($i = 0; $i < $Quantity; $i++) {
        $password .= $word[rand() % $len];
    }
    return $password;
}




class View {
    
    static function cache($filename,$param = []){
        return View::_match_view($filename, $param);
    }
    
    static function display($filename,$param = []){
        echo View::_match_view($filename, $param);
    }
    
    static private function _match_view($filename,$param){
        ob_start();
        extract($param);
        include($filename.'.php');
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }
    
}
