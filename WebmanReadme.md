<h1 style="text-align: center;"> support </h1>

## 安装

```shell
$ composer require zhenmu/support -vvv
```

## 使用

1. 通过 `./webman make:controller` 控制器生成后，继承同目录下的 `WebmanBaseController` 基类。
2. 编写接口时可通过 `$this->success($data = [], $err_code = 200, $messsage = 'success');` 返回正确数据给接口。
3. 编写接口时可通过 `$this->fail($messsage = '', $err_code = 400);` 返回错误信息给接口。
4. 在 `support/exception/Handler.php` 的 `render` 函数中，调用 `WebmanResponseTrait` 的 `$this->renderableHandle($request, $exception);` 示例见下方错误处理。


### 控制器

```php
<?php

namespace support\exception;

use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;
use Webman\Exception\ExceptionHandler;
use ZhenMu\Support\Traits\WebmanResponseTrait;

/**
 * Class Handler
 * @package support\exception
 */
class Handler extends ExceptionHandler
{
    use WebmanResponseTrait; // 这里需要引入 WebmanResponseTrait

    public $dontReport = [
        BusinessException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        return $this->renderableHandle($request, $exception); // 这里进行调用，做了一些错误捕捉
    }
}
```

## 控制器调用

```
<?php

namespace app\controller;

class DemoController extends WebmanBaseController
{
    public function index()
    {
        // validate data
        \validator()->validate(\request(), [
            'name' => 'required|string',
            'age' => 'nullable|integer',
        ]);

        // your logic
        $error = false;
        if ($error) {
            throw new \RuntimeException('error message');
        }

        return $this->success([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
    }
}
```
