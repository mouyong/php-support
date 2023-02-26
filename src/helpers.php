<?php

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            if (class_exists(\Illuminate\Support\HigherOrderTapProxy::class)) {
                return new \Illuminate\Support\HigherOrderTapProxy($value);
            }

            return new \ZhenMu\Support\Utils\HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('sendRequest')) {
    /**
     * Http 请求
     * 
     * @param string $url 请求网址
     * @param string $method 请求方式
     * @param array $params 请求参数
     * @param array $headers 请求头
     * 
     * @return bool|mixed
     */
    function sendRequest(string $url, string $method = "GET", array $params = [], array $headers = [])
    {
        $method = strtoupper($method);

        $httpInfo = array();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));

        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return [
                'code' => 1,
                'message' => "cURL Error: " . curl_error($ch),
                'data' => [
                    'httpCode' => $httpCode,
                    'httpInfo' => $httpInfo,
                    'response' => $response,
                ],
            ];
        }

        curl_close($ch);
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'response' => $httpCode,
                'httpInfo' => $httpInfo,
                'response' => $response,
            ],
        ];
    }
}
