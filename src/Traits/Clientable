<?php

namespace ZhenMu\Support\Traits;

trait Clientable
{
    use Arrayable;

    /** @var \GuzzleHttp\Psr7\Response */
    protected $response;

    protected array $result = [];

    public static function make()
    {
        return new static();
    }

    abstract public function getHttpClient();

    abstract public function handleEmptyResponse(?string $content = null);

    abstract public function isErrorResponse(): bool;

    abstract public function handleErrorResponse(?string $content = null);

    abstract public function hasPaginate(): bool;

    abstract public function getTotal(): ?int;

    abstract public function getPageSize(): ?int;

    abstract public function getCurrentPage(): ?int;

    abstract public function getLastPage(): ?int;

    abstract public function getDataList(): static|array|null;

    protected function castResponse()
    {
        $this->result = json_decode($content = $this->response->getBody()->getContents(), true) ?? [];
        $this->attributes = $this->result;

        if (empty($this->result)) {
            $this->handleEmptyResponse($content);
        }

        if ($this->isErrorResponse()) {
            $this->handleErrorResponse($content);
        }

        return true;
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
        $this->response = $this->getHttpClient()->$method(...$args);

        $this->castResponse();

        return $this;
    }
}
