<?php

namespace ZhenMu\Support\Utils;

class Str
{
    /**
     * It returns true if the variable is a pure integer, false otherwise.
     *
     * @param mixed variable The variable to check.
     * @return A boolean value.
     */
    public static function isPureInt(mixed $variable)
    {
        return preg_match('/^\d*?$/', $variable);
    }
}
