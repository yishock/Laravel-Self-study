# 雖然這些上網查詢資料 就都能查到 但我還是記錄一下

時間戳記 轉換為 YYYY-mm-dd HH:ii:ss 的做法 (台灣時區)
```
DATE_ADD(FROM_UNIXTIME([資料欄位], '%Y-%m-%d %H:%i:%s'),INTERVAL 8 HOUR) 
```

兩個資料合併
UNION 和 UNION ALL
UNION 是會合併掉 重複的資料(看 SELECT 顯示的欄位來合併)
UNION ALL 是不會合併 A+B 的全部資料 (3+3=6)
```
SELECT P_Name FROM products_taiwan
UNION
SELECT P_Name FROM products_china;

SELECT P_Name FROM products_taiwan
UNION ALL
SELECT P_Name FROM products_china;
```
