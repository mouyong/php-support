<?php

namespace ZhenMu\Support\Traits;

trait PimpleApplicationTrait
{
    protected function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register(new $provider);
        }
    }

    public function __get($name)
    {
        if (in_array($name, $this->keys())) {
            return $this[$name];
        }

        throw new \InvalidArgumentException("Class $name doesnt exists");
    }
}