<h1 style="text-align: center;"> support </h1>

## 安装

```shell
$ composer require zhenmu/support -vvv
```

## 使用

### 控制器

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use ZhenMu\Support\Traits\ResponseTrait; // here

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ResponseTrait; // here
}

```


### 错误处理

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use ZhenMu\Support\Traits\ResponseTrait; // here

class Handler extends ExceptionHandler
{
    use ResponseTrait; // here

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        
        $this->renderable($this->renderableHandle()); // here
    }
}

```

**注意：头请求未声明此次请求需要返回 json 数据时，`$this->fail($message, $err_code)` 的错误码需要符合 http status_code 响应码**
