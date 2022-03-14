<?php

namespace ZhenMu\Support\Traits;

use ZhenMu\Support\Utils\Str;
use Symfony\Component\HttpFoundation\Response;

trait WebmanResponseTrait
{
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

        $err_msg = Str::string2utf8($err_msg);

        if ($err_code === 200 && ($config_err_code = config('webman-init-template.response.err_code', 200)) !== $err_code) {
            $err_code = $config_err_code;
        }

        $res = compact('err_code', 'err_msg', 'data') + array_filter(compact('meta'));

        return \response(
            \json_encode($res, \JSON_UNESCAPED_SLASHES|\JSON_UNESCAPED_UNICODE),
            Response::HTTP_OK,
            array_merge([
                'Content-Type' => 'application/json',
            ], $headers)
        );
    }

    public function fail($err_msg = 'unknown error', $err_code = 400, $data = [], $headers = [])
    {
        if (! \request()->expectsJson()) {
            if (!array_key_exists($err_code, Response::$statusTexts)) {
                throw new \LogicException(sprintf("<b style='color: red;'>$err_code</b> is invalid <b style='color: red;'>http status_code</b> when use HTTP Response. Call in %s::%s", \request()->controller, \request()->action));
            }

            return \response(
                $err_msg,
                $err_code,
                array_merge([
                ], $headers)
            );
        }

        return $this->success($data, $err_msg ?: 'unknown error', $err_code ?: 500);
    }

    public function reportableHandle(\Throwable $e)
    {
        //
    }

    public function renderableHandle(\Webman\Http\Request $request, \Throwable $e)
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->fail($e->validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->fail('404 Data Not Found.', Response::HTTP_NOT_FOUND);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->fail('404 Url Not Found.', Response::HTTP_NOT_FOUND);
        }

        return $this->fail($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
