# 發現 Laravel 神秘的框架概念
## 服務容器(Service Container) 跟 服務提供者(Service Providers)

目前測試的範例
```
'providers' => [
        /*
         * Laravel Framework Service Providers...
         */
         .....
        /*
         * Package Service Providers...
         */
         App\Providers\AppServiceProvider::class,
]
'aliases' => [
         .....
        'Response'  => Illuminate\Support\Facades\Response::class,
         .....
]
```

其中只有**App\Providers\AppServiceProvider::class**是我們新增的項目，

目標是 **response()->aescode($yourData);** 執行時會需要幫我們把$yourData儲存進DB當log紀錄。

這是我請 GTP 幫我產出的範例程式碼
```
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use App\Models\AesLog;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Response::macro('aescode', function ($jsonOutputData) {
            // 儲存到資料庫
            AesLog::create([
                'data' => json_encode($jsonOutputData),
            ]);

            // 回傳訊息
            return response()->json([
                'status' => 'saved',
                'message' => 'Data stored successfully',
            ]);
        });
    }

    public function register()
    {
        //
    }
}
```



參考網站：
- [laravel 官方介紹Service Container](https://laravel.com/docs/12.x/container)
+ [laravel 官方介紹Service Providers](https://laravel.com/docs/12.x/providers)
