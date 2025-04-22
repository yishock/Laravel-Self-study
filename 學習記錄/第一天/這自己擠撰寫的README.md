# Laravel PHP 框架
本案內容
## 版本需求
php版本：7.4
mysql版本：8.1
Laravel版本：5.2.45

## 最簡單的架設方式
需要安裝 docker、laradock
參考```Laradock架設教學```中的教學操作即可，只要需要注意php與mysql版本需要做修改。
如果需要不同php版本```Laradock多php版本設定教學```文件做修改

### 實際架設流程其實就是依文章的操作說一遍而已
隨便一個方便管理專案的地方(沒有的自行新增資料夾並進入資料夾中)，
在當下資料夾開啟CMD確認資料夾目錄正確實執行
```git clone https://github.com/laradock/laradock.git```
安裝laradock的東西，這裡很大所以跑一下。
接下來看你要跟教學一樣新增一個資料夾裡面放樂活的專案資料夾還是直接放在同層(都可以)，
但就先照教學的一樣新增一個`www`的資料夾來存放。

`laradock`裝好後會多一個資料夾`laradock`，進入後複製`.env.example`並改名為`.env`這個就是基礎的設定檔了。
開啟`.env`後修改一下樂活需要用的環就
```# 修改幾個重點
# 要到上一層的www放置各項專案的位置
APP_CODE_PATH_HOST=../www
# mydql 版本
MYSQL_VERSION=8.1
# php 版本
PHP_VERSION=8.1
```
修改儲存完畢後，
進入
```nginx/sites/```
這個資料中複製`laravel.conf.example`並更名為`laravel.test.conf`(其實我也不知道為什麼要叫這個檔名，反正就照教學跑)
可以檔案中的範例做修改即可，
修改的部分
```
# 這裡是設定網域名稱 需要對應最後一部的 hosts 設定
server_name {網域};
# 這裡是修改為你的資料夾名稱
root /var/www/{資料夾名稱}/public;
以下我的範例
server_name test_php.test;
root /var/www/test_php/public;
```
修改與複製的數量會跟你的專案數一樣，
所以可以先複製四個
```
server {}
```
設定自己測試的網域與更改為專案的資料夾名稱。
後續可以先去設定 hosts 這邊不好解釋可以直接參考`hosts設定教學`的資料夾來存放。

下一步就是設定權限了
因為我是MAC所以我說一下操作順序，
1.對著專案資料夾(test_php)按右鍵選擇`取得資訊`
2.按右下角的鎖頭把它打開
3.把權限都改成`讀取和寫入`
4.再按左下角有個圈圈與三個點的圖案選擇`套用至內含的項目`
設定完後，這個專案的資料夾權限就設定完了，以上是用在於自己本機的專案，如果是測試版或正式版是不要這樣用！

最後一步驟就是新增專案所需的套件了

開啟CMD進入laradock資料夾
```# 進入laradock資料夾 因為我不知道各自的路徑 所以自行對應自己的路徑
cd laradock
```
之後執行
```
# 讓它安裝跑一下 這裡除了第一次要執行以外 如果有修改 laradock的設定 都需要執行
docker-compose up -d nginx mysql phpmyadmin
# 之後就能改這樣執行了
docker compose exec workspace bash
```
進入後
```# 進入專案的資料夾
cd test_php
# 並執行 安裝相依套件
composer install
```
基本就完成了！
最後一步把專案中的
`.env.example`複製出來改名為`.env`
其中`DB_`相關的設定如果你跟著教學跑的話可以這樣設定
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE={資料表名稱}
DB_USERNAME=root
DB_PASSWORD=root
```
這裡需要看一下
```
APP_KEY=xxxxxxxxxxxxxx
```
這邊是不是有資料的，沒有的話，可以拿這組'傳承金鑰'來處理
如果要自己產生的話，到上述輸入指令的地方輸入
```
php artisan key:generate
```

只要打開瀏覽器看有沒有顯示成功
``` # 依樂活本案為範例
http://test_php.test/index
```

## 參考資料
Laradock架設教學：https://blog.twjoin.com/%E4%B8%80%E6%AD%A5%E6%AD%A5%E6%95%99%E6%82%A8%E4%BD%BF%E7%94%A8laradock-%E5%BF%AB%E9%80%9F%E6%89%93%E9%80%A0laravel-php%E7%92%B0%E5%A2%83-15f046d64c34
Laradock多php版本設定教學：https://ericwu.asia/archives/535
hosts設定教學：https://blog.gtwang.org/windows/windows-linux-hosts-file-configuration/
