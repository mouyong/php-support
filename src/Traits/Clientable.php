<?php

namespace Plugins\FresnsEngine;

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
        $this->response = $this->getHttpClient()->$method(...$args);

        $this->result  = $this->castResponse($this->response);

        $this->attributes = $this->result;

        return $this;
    }
}
