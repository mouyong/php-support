<?php

namespace ZhenMu\Support\Utils;

class Excel
{
    public static function datetimeFromCell($datetime = null, $format = 'Y-m-d H:i:s')
    {
        if (!$datetime) {
            return null;
        }

        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($datetime)?->format($format);
    }
    
    public static function cellToString($format = '="%s"', ...$value)
    {
        if (!str_starts_with($format, '=')) {
            $value = [$format];
            $format = '="%s"';
        }

        if (!$value) {
            return null;
        }

        return sprintf($format, ...$value);
    }
}
