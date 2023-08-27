<?php

namespace ZhenMu\Support\Contracts;

use Psr\Http\Message\RequestInterface;

interface AccessToken
{
    public function applyToRequest(RequestInterface $request, array $options);
    
    public function refresh();

    public function getToken($refresh = false);
}