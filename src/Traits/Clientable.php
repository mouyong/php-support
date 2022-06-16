<?php

namespace ZhenMu\Support\Traits;

trait Clientable
{
    use Arrayable;

    /** @var \GuzzleHttp\Psr7\Response */
    protected $response;

    protected array $result = [];

    public static function make(): static|\GuzzleHttp\Promise\Utils|\GuzzleHttp\Client
    {
        return new static();
    }

    abstract public function getBaseUri(): ?string;

    public function getOptions()
    {
        return [
            'base_uri' => $this->getBaseUri(),
            'timeout' => 5, // 请求 5s 超时
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }

    public function getHttpClient()
    {
        return new \GuzzleHttp\Client($this->getOptions());
    }

    abstract public function handleEmptyResponse(?string $content = null, ?\Psr\Http\Message\ResponseInterface $response = null);

    abstract public function isErrorResponse(array $data): bool;

    abstract public function handleErrorResponse(?string $content = null, array $data = []);

    abstract public function hasPaginate(): bool;

    abstract public function getTotal(): ?int;

    abstract public function getPageSize(): ?int;

    abstract public function getCurrentPage(): ?int;

    abstract public function getLastPage(): ?int;

    abstract public function getDataList(): static|array|null;

    public function castResponse($response)
    {
        $data = json_decode($content = $response->getBody()->getContents(), true) ?? [];

        if (empty($data)) {
            $this->handleEmptyResponse($content, $response);
        }

        if ($this->isErrorResponse($data)) {
            $this->handleErrorResponse($content, $data);
        }

        return $data;
    }

    public function paginate()
    {
        if (!$this->hasPaginate()) {
            return null;
        }

        $paginate = new \Illuminate\Pagination\LengthAwarePaginator(
            items: $this->getDataList(),
            total: $this->getTotal(),
            perPage: $this->getPageSize(),
            currentPage: $this->getCurrentPage(),
        );

        $paginate
            ->withPath('/'.\request()->path())
            ->withQueryString();

        return $paginate;
    }

    public function __call($method, $args)
    {
        // 异步请求处理
        if (method_exists(\GuzzleHttp\Promise\Utils::class, $method)) {
            $results = call_user_func_array([\GuzzleHttp\Promise\Utils::class, $method], $args);

            if (!is_array($results)) {
                return $results;
            }

            $data = [];
            foreach ($results as $key => $promise) {
                $data[$key] = $this->castResponse($promise);
            }

            $this->attributes = $data;

            return $this;
        }

        // 同步请求
        if (method_exists($this->getHttpClient(), $method)) {
            $this->response = $this->getHttpClient()->$method(...$args);
        }

        // 响应结果处理
        if ($this->response instanceof \GuzzleHttp\Psr7\Response) {
            $this->result  = $this->castResponse($this->response);

            $this->attributes = $this->result;
        }

        // 将 promise 请求直接返回
        if ($this->response instanceof \GuzzleHttp\Promise\Promise) {
            return $this->response;
        }

        return $this;
    }
}
