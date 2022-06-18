<?php

namespace ZhenMu\Support\Traits;

use Psr\Http\Message\ResponseInterface;

trait DefaultClient
{
    public function getBaseUri(): ?string
    {
        return null;
    }

    public function handleEmptyResponse(?string $content = null, ?ResponseInterface $response = null)
    {
        return null;
    }

    public function isErrorResponse(array $data): bool
    {
        return false;
    }

    public function handleErrorResponse(?string $content = null, array $data = [])
    {
        return null;
    }

    public function hasPaginate(): bool
    {
        return false;
    }

    public function getTotal(): ?int
    {
        return 0;
    }

    public function getPageSize(): ?int
    {
        return 0;
    }

    public function getCurrentPage(): ?int
    {
        return 0;
    }

    public function getLastPage(): ?int
    {
        return 0;
    }

    public function getDataList(): static|array|null
    {
        return null;
    }
}
