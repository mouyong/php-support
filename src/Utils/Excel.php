<?php

namespace ZhenMu\Support\Utils;

use Carbon\Carbon;

class Excel
{
    public static function datetimeFromCell($datetime = null, $format = 'Y-m-d H:i:s', $soruceFormat = 'Y-m-d H:i:s')
    {
        if (!$datetime) {
            return null;
        }

        if (is_string($datetime)) {
            $datetime = match(true) {
                default => null,
                str_contains($datetime, '-') && str_contains($datetime, ':') => Carbon::createFromFormat($soruceFormat, $datetime)->format($format),
                str_contains($datetime, '/') && !str_contains($datetime, ':') => Carbon::createFromDate($datetime)->format($format),
            };

            return $datetime;
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
