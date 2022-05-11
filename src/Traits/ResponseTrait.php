<?php

namespace ZhenMu\Support\Traits;

use Symfony\Component\HttpFoundation\Response;

/**
 * @see https://github.com/mouyong/php-support/blob/master/src/Traits/ResponseTrait.php
 */
trait ResponseTrait
{
    public static function string2utf8($string = '')
    {
        if (empty($string)) {
            return $string;
        }

        $encoding_list = [
            "ASCII",'UTF-8',"GB2312","GBK",'BIG5'
        ];

        $encode = mb_detect_encoding($string, $encoding_list);

        $string = mb_convert_encoding($string, 'UTF-8', $encode);

        return $string;
    }

    public function paginate(\Illuminate\Pagination\LengthAwarePaginator $paginate, ?callable $callable = null)
    {
        return $this->success([
            'meta' => [
                'total' => $paginate->total(),
                'current_page' => $paginate->currentPage(),
                'per_page' => $paginate->perPage(),
                'last_page' => $paginate->lastPage(),
            ],
            'data' => array_map(function ($item) use ($callable) {
                if ($callable) {
                    return $callable($item) ?? $item;
                }

                return $item;
            }, $paginate->items()), 
        ]);
    }

    public function success($data = [], $err_msg = 'success', $err_code = 200, $headers = [])
    {
        if (is_string($data)) {
            $err_code = $err_msg;
            $err_msg = $data;
            $data = [];
        }

        // 处理 meta 数据
        $meta = [];
        if (isset($data['data']) && isset($data['meta'])) {
            extract($data);
        }

        $err_msg = static::string2utf8($err_msg);

        if ($err_code === 200 && ($config_err_code = config('laravel-init-template.response.err_code', 200)) !== $err_code) {
            $err_code = $config_err_code;
        }

        $res = compact('err_code', 'err_msg', 'data') + array_filter(compact('meta'));

        return \response(
            \json_encode($res, \JSON_UNESCAPED_SLASHES|\JSON_UNESCAPED_UNICODE|\JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            array_merge([
                'Content-Type' => 'application/json',
            ], $headers)
        );
    }

    public function fail($err_msg = 'unknown error', $err_code = 400, $data = [], $headers = [])
    {
        if (! \request()->wantsJson()) {
            $err_msg = \json_encode(compact('err_code', 'err_msg', 'data'), \JSON_UNESCAPED_SLASHES|\JSON_UNESCAPED_UNICODE|\JSON_PRETTY_PRINT);
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

    public function reportableHandle()
    {
        return function (\Throwable $e) {
            //
        };
    }

    public function renderableHandle()
    {
        return function (\Throwable $e) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return $this->fail('登录失败，请稍后重试', $e->getCode() ?: config('laravel-init-template.auth.unauthorize_code', 401));
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

            \info('error', [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file_line' => sprintf('%s:%s', $e->getFile(), $e->getLine()),
            ]);

            return $this->fail($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        };
    }
}
