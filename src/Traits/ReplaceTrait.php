<?php

namespace ZhenMu\Support\Traits;

trait ReplaceTrait
{
    public function getReplaceKeys($content)
    {
        preg_match_all('/(\$[^\s.]*?\$)/', $content, $matches);

        $keys = $matches[1] ?? [];

        return $keys;
    }

    public function getReplacesByKeys(array $keys)
    {
        $replaces = [];
        foreach ($keys as $key) {            
            $currentReplacement = str_replace('$', '', $key);

            $currentReplacementLower = Str::of($currentReplacement)->lower()->toString();
            $method = sprintf("get%sReplacement", Str::studly($currentReplacementLower));

            if (method_exists($this, $method)) {
                $replaces[$currentReplacement] = $this->$method();
            } else {
                \info($currentReplacement . " does match any replace content");
                // keep origin content
                $replaces[$currentReplacement] = $key;
            }
        }

        return $replaces;
    }

    public function getReplacedContent(string $content, array $keys = [])
    {
        if (!$keys) {
            $keys = $this->getReplaceKeys($content);
        }

        $replaces = $this->getReplacesByKeys($keys);

        return str_replace($keys, $replaces, $content);
    }
}
