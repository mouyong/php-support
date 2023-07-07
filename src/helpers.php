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

if (!function_exists('curl')) {
    /**
     * Http 请求
     *
     * @param string $method 请求方式: GET, POST, PUT, PATCH, DELETE
     * @param string $url 请求网址
     * @param array $params 请求参数
     * @param array $headers 请求头
     * @param array $config 请求配置
     *
     * @return bool|array
     *
     * @example:
     *
     * $resp=curl("http://httpbin.org/ip", 'get', []);
     * die($resp['data']['response']);
     */
    function curl(string $method, string $url, array $params = [], array $headers = [], array $config = [])
    {
        $method = strtoupper($method);

        $httpInfo = array();

        $ch = curl_init();

        $options = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true, // 要求结果为字符串且输出到屏幕上
        ];

        // 设置代理
        if (!empty($config['proxy']['host']) && !empty($config['proxy']['port'])) {
            $options[CURLOPT_PROXY] = $config['proxy']['host'];
            $options[CURLOPT_PROXYPORT] = $config['proxy']['port'];
        }

        // 设置证书
        if (!empty($config['use_cert']) && $config['use_cert'] === true) {
            if (!empty($config['use_cert']['ssl_cert_path']) && !empty($config['use_cert']['ssl_key_path'])) {
			    //使用证书：cert 与 key 分别属于两个 .pem 文件
                $options[CURLOPT_SSLCERTTYPE] = 'PEM';
                $options[CURLOPT_SSLCERT] = $config['use_cert']['ssl_cert_path'];
                $options[CURLOPT_SSLKEYTYPE] = 'PEM';
                $options[CURLOPT_SSLKEY] = $config['use_cert']['ssl_key_path'];
            }
        }

        if ($method === 'JSON' && !in_array('application/json', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        // 设置header
        if (!empty($headers)) {
            // curl 请求的 headers 必须处理成 array("xxx: xxx", "xxx: xxx") 格式的数组
            $options[CURLOPT_HTTPHEADER] = array_map(function ($key, $value) {
                return $key . ': '. $value;
            }, array_keys($headers), $headers);
        }

        if (stripos($url, "https://") !== false) {
            $options[CURLOPT_SSLVERSION] =  CURL_SSLVERSION_TLSv1;
            $options[CURLOPT_SSL_VERIFYPEER] =  false; // 对认证证书来源的检
            $options[CURLOPT_SSL_VERIFYHOST] =  false; // 从证书中检查SSL加密算法是否存
        } else {
            $options[CURLOPT_SSL_VERIFYPEER] =  true;
            $options[CURLOPT_SSL_VERIFYHOST] =  2; //严格校验
        }

        if ($method === 'POST' || $method == 'JSON') {
            if (in_array('application/json', $headers)) {
                $params = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

                $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            } else {
                $options[CURLOPT_POST] = true;
            }

            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POSTFIELDS] = $params;
        } else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
                }

                $options[CURLOPT_URL] = $url . '?' . $params;
            } else {
                $options[CURLOPT_URL] = $url;
            }
        }

        curl_setopt_array($ch, $options);

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

        if (stripos($response, 'Sfdump') !== false || stripos($response, 'exception') !== false) {
            mdump('server debug:');
            mdd($response);
        }

        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'httpCode' => $httpCode,
                'httpInfo' => $httpInfo,
                'response' => $response,
            ],
        ];
    }
}

if (!function_exists('p')) {
    /**
     * 调试方法
     *
     * @param array $data [description]
     */
    function p($data, $die = 1)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        if ($die) die;
    }
}

if (!function_exists('mdump')) {
    /**
     * 调试方法
     *
     * @param array $data [description]
     */
    function mdump()
    {
        foreach (func_get_args() as $item) {
            p($item, 0);
        }
    }
}

if (!function_exists('mdd')) {
    /**
     * 调试方法
     *
     * @param array $data [description]
     */
    function mdd()
    {
        mdump(...func_get_args());
        die;
    }
}
