<?php

namespace ZhenMu\Support\Utils;

use Illuminate\Support\Arr;

class Json
{
    protected $filepath;

    protected $data = [];

    public function __construct(?string $filepath = null)
    {
        $this->filepath = $filepath;

        $this->decode();
    }

    public static function make(?string $filepath = null)
    {
        return new static($filepath);
    }

    public function decode(?string $content = null)
    {
        if ($this->filepath && file_exists($this->filepath)) {
            $content = @file_get_contents($this->filepath);
        }

        if (!$content) {
            $content = '';
        }

        $this->data = json_decode($content, true) ?? [];

        return $this;
    }

    public function get(?string $key = null, $default = null)
    {
        if (!$key) {
            return $this->data;
        }

        return Arr::get($this->data, $key, $default);
    }
}
