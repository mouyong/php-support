<?php

namespace ZhenMu\Support\Utils;

class Excel
{
    public static function datetime($datetime = null, $format = 'Y-m-d H:i:s')
    {
        if (!$datetime) {
            return null;
        }

        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($datetime)->format($format);
    }
}
