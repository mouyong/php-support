<?php

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            if (class_exists(\Illuminate\Support\HigherOrderTapProxy::class)) {
                return new \HigherOrderTapProxy\HigherOrderTapProxy($value);
            }

            return new \ZhenMu\Support\Utils\HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}
