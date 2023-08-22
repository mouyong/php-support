<?php

namespace ZhenMu\Support\Traits;

use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://github.com/mouyong/php-support/blob/master/src/Traits/ResponseTrait.php
 */
trait WebmanResponseTrait
{
    public static $responseCodeKey = 1; // 1:code msg、2:code message、3:err_code err_msg、errcode errmsg
    public static $responseSuccessCode = 1; // 0,200

    public static function setResponseCodeKey(int $responseCodeKey = 1)
    {
        static::$responseCodeKey = $responseCodeKey;
    }

    public static function setResponseSuccessCode(int $err_code = 200)
    {
        static::$responseSuccessCode = $err_code;
    }

    public static function string2utf8($string = '')
    {
        if (empty($string)) {
            return $string;
        }

        $encoding_list = [
            "ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'
        ];

        $encode = mb_detect_encoding($string, $encoding_list);

        $string = mb_convert_encoding($string, 'UTF-8', $encode);

        return $string;
    }

    public function customPaginate($items, $total, $pageSize = 15)
    {
        $paginate = new \Illuminate\Pagination\LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $pageSize,
            currentPage: \request('page'),
        );

        $paginate
            ->withPath('/' . \request()->path())
            ->withQueryString();

        return $this->paginate($paginate);
    }

    public function paginate($data, ?callable $callable = null)
    {
        // 处理集合数据
        if ($data instanceof \Illuminate\Database\Eloquent\Collection) {
            return $this->success(array_map(function ($item) use ($callable) {
                if ($callable) {
                    return $callable($item) ?? $item;
                }

                return $item;
            }, $data->all()));
        }

        // 处理非分页数据
        if (!$data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return $this->success($data);
        }

        // 处理分页数据
        $paginate = $data;
        return $this->success([
            'meta' => [
                'total' => $paginate->total(),
                'current_page' => $paginate->currentPage(),
                'page_size' => $paginate->perPage(),
                'last_page' => $paginate->lastPage(),
            ],
            'data' => array_map(function ($item) use ($callable) {
                if ($callable) {
                    return $callable($item) ?? $item;
                }

                return $item;
            }, $paginate?->items()),
        ]);
    }

    public function success($data = [], $err_msg = 'success', $err_code = 200, $headers = [])
    {
        if (is_string($data)) {
            $err_code = is_string($err_msg) ? $err_code : $err_msg;
            $err_msg = $data;
            $data = [];
        }

        // 处理 meta 数据
        $meta = [];
        if (isset($data['data']) && isset($data['meta'])) {
            extract($data);
        }

        $err_msg = static::string2utf8($err_msg);

        if ($err_code !== static::$responseSuccessCode) {
            $err_code = static::$responseSuccessCode;
        }

        $data = $data ?: null;

        $res = match (static::$responseCodeKey) {
            default => [
                'err_code' => $err_code,
                'err_msg' => $err_msg,
                'data' => $data,
            ],
            1 => [
                'err_code' => $err_code,
                'err_msg' => $err_msg,
                'data' => $data,
            ],
            2 => [
                'code' => $err_code,
                'message' => $err_msg,
                'data' => $data,
            ],
            3 => [
                'code' => $err_code,
                'msg' => $err_msg,
                'data' => $data,
            ],
            4 => [
                'errcode' => $err_code,
                'errmsg' => $err_msg,
                'data' => $data,
            ],
        };

        $res = $res + array_filter(compact('meta'));

        return \response(
            \json_encode($res, \JSON_UNESCAPED_SLASHES|\JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            array_merge([
                'Content-Type' => 'application/json',
            ], $headers)
        );
    }

    public function fail($err_msg = 'unknown error', $err_code = 400, $data = [], $headers = [])
    {
        $res = match (static::$responseCodeKey) {
            default => [
                'err_code' => $err_code,
                'err_msg' => $err_msg,
                'data' => $data,
            ],
            1 => [
                'err_code' => $err_code,
                'err_msg' => $err_msg,
                'data' => $data,
            ],
            2 => [
                'code' => $err_code,
                'message' => $err_msg,
                'data' => $data,
            ],
            3 => [
                'code' => $err_code,
                'msg' => $err_msg,
                'data' => $data,
            ],
            4 => [
                'errcode' => $err_code,
                'errmsg' => $err_msg,
                'data' => $data,
            ],
        };

        if (!\request()->expectsJson()) {
            $err_msg = \json_encode($res, \JSON_UNESCAPED_SLASHES|\JSON_PRETTY_PRINT);
            if (!array_key_exists($err_code, Response::$statusTexts)) {
                $err_code = 500;
            }

            return \response(
                $err_msg,
                $err_code,
                array_merge([
                    'Content-Type' => 'application/json',
                ], $headers)
            );
        }

        return $this->success($data, $err_msg ?: 'unknown error', $err_code ?: 500, $headers);
    }

    public function reportableHandle(\Throwable $e)
    {
        //
    }

    public function renderableHandle()
    {
        return function (\Throwable $e) {
            if (! \request()->expectsJson()) {
                return;
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return $this->fail('未登录', $e->getCode() ?: config('laravel-init-template.auth.unauthorize_code', 401));
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException) {
                if (\request()->expectsJson()) {
                    return $this->fail('未授权', $e->getStatusCode());
                }

                return \response()->noContent($e->getStatusCode(), $e->getHeaders());
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $message = '请求失败';
                if ($e->getStatusCode() == 403) {
                    $message = '拒绝访问';
                }

                return $this->fail($message, $e->getStatusCode());
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return $this->fail($e->validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->fail('404 Data Not Found.', Response::HTTP_NOT_FOUND);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return $this->fail('404 Url Not Found.', Response::HTTP_NOT_FOUND);
            }

            $code = $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            if (method_exists($e, 'getStatusCode')) {
                $code = $e->getStatusCode();
            }

            // \info('error', [
            //     'class' => get_class($e),
            //     'code' => $code,
            //     'message' => $e->getMessage(),
            //     'file_line' => sprintf('%s:%s', $e->getFile(), $e->getLine()),
            // ]);

            return $this->fail($e->getMessage(), $code);
        };
    }
}
