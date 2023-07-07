<?php

namespace ZhenMu\Support\Http;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use ZhenMu\Support\Contracts\AccessToken;

abstract class AbstractRequestClient extends Request
{
    protected $app;

    protected $baseUri = '';

    public static $accessToken = null;

    public function __construct($app = null, $accessToken = null)
    {
        $this->app = $app;

        static::$accessToken = $accessToken;
    }

    public function setAccessToken(AccessToken $accessToken)
    {
        static::$accessToken = $accessToken;

        return $this;
    }

    public function request($url, $method = 'GET', $options = [])
    {
        if (empty($this->middlewares)) {
            $this->registerHttpMiddlewares();
        }

        $response = $this->performRequest($this->getRequestUrl($url), $method, $options);

        $response = $this->detectAndCastResponseToType($response, $this->getResponseType());

        if ($this->getResponseType()) {
            return $response->toArray();
        }

        return $response;
    }

    public function getResponseType()
    {
        return null;
    }

    protected function registerHttpMiddlewares()
    {
        // retry
        $this->pushMiddleware($this->retryMiddleware(), 'retry');
        // access token
        $this->pushMiddleware($this->accessTokenMiddleware(), 'access_token');
        // log
        if (in_array('logger', $this->app->keys())) {
            $this->pushMiddleware($this->logMiddleware(), 'log');
        }
    }


    /**
     * Attache access token to request query.
     *
     * @return \Closure
     */
    protected function accessTokenMiddleware()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (static::$accessToken instanceof AccessToken) {
                    $request = static::$accessToken->applyToRequest($request, $options);
                }

                return $handler($request, $options);
            };
        };
    }

    /**
     * Log the request.
     *
     * @return \Closure
     */
    protected function logMiddleware()
    {
        $formatter = new MessageFormatter($this->app['config']['http.log_template'] ?? MessageFormatter::DEBUG);

        return Middleware::log($this->app['logger'], $formatter, LogLevel::DEBUG);
    }

    /**
     * Return retry middleware.
     *
     * @return \Closure
     */
    protected function retryMiddleware()
    {
        return Middleware::retry(
            function (
                $retries,
                RequestInterface $request,
                ResponseInterface $response = null
            ) {
                // Limit the number of retries to 2
                if ($retries < ($this->app->config['http']['max_retries'] ?? 1) && $response && $body = $response->getBody()) {
                    // Retry on server errors
                    $response = json_decode($body, true);

                    if ($this->isRetryResponse($response)) {
                        if (static::$accessToken instanceof AccessToken) {
                            static::$accessToken->refresh();
                        }

                        if (in_array('logger', $this->app->keys())) {
                            $this->app['logger']->debug('Retrying with refreshed access token.');
                        }

                        return true;
                    }
                }

                return false;
            },
            function () {
                return abs($this->app->config['http.retry_delay'] ?? 500);
            }
        );
    }

    public function isRetryResponse(?array $response)
    {
        // DEMO: return !empty($response['code']) && in_array(abs($response['code']), [], true);
        return false;
    }
}