<?php

namespace ZhenMu\Support\Http;

use ZhenMu\Support\Traits\HasHttpRequests;

class Request
{
    use HasHttpRequests;

    protected $baseUri;

    protected function getBaseUri()
    {
        return rtrim($this->baseUri, '/');
    }

    protected function getRequestUrl($url = '')
    {
        return sprintf('%s/%s', $this->getBaseUri(), ltrim($url, '/'));
    }

    public function httpGet(string $url, array $data, array $options = [])
    {
        return $this->request($url, 'GET', [
                'query' => $data,
            ] + $options);
    }

    public function httpPost(string $url, array $data, array $options = [])
    {
        return $this->request($url, 'POST', [
                'form_params' => $data,
            ] + $options);
    }

    public function httpPostJson(string $url, array $data = [], array $query = [], array $options = [])
    {
        return $this->request($url, 'POST', ['query' => $query, 'json' => $data] + $options);
    }

    public function httpUpload(string $url, array $files = [], array $form = [], array $query = [], array $options = [])
    {
        $multipart = [];
        $headers = [];

        if (isset($form['filename'])) {
            $headers = [
                'Content-Disposition' => 'form-data; name="media"; filename="'.$form['filename'].'"'
            ];
        }

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name' => $name,
                'contents' => fopen($path, 'r'),
                'headers' => $headers
            ];
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request(
            $url,
            'POST',
            ['query' => $query, 'multipart' => $multipart, 'connect_timeout' => 30, 'timeout' => 30, 'read_timeout' => 30] + $options
        );
    }

    public function httpDelete(string $url, array $data, array $options = [])
    {
        return $this->request($url, 'DELETE', [
                'form_params' => $data,
            ] + $options);
    }
}