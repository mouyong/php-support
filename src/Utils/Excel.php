<?php

namespace ZhenMu\Support\Utils;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\Event;

class Excel
{
    public static function handleCalculateSheet(Event $event)
    {
        $sheet = $event->sheet->getDelegate();

        if (! $sheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet) {
            return;
        }

        $maxCol = ord($sheet->getHighestColumn()) - 64;
        $maxColName = chr($maxCol + 65);
        $maxRow = $sheet->getHighestRow();

        foreach (range(0, $maxRow) as $row) {
            foreach (range(0, $maxCol) as $col) {
                $colName = chr($col + 65);
                $cell = "{$colName}{$row}";

                try {
                    $calcValue = $sheet->getCell($cell)->getCalculatedValue();
                    $newValue = $calcValue;
                } catch (\Throwable $e) {
                    $value = $sheet->getCell($cell)->getValue();

                    info("获取单元格 {$cell} 计算结果错误", [
                        'code' => $e->getCode(), 
                        'message' => $e->getMessage(),
                        'cell' => $cell,
                        'origin_value' => $value,
                    ]);

                    $newValue = $value;
                }

                $sheet->getCell($cell)->setValue($newValue);
            }
        }

        info("最大单元格为 {$cell}, 最大列: {$maxColName} 最大行号: {$maxRow}");
    }
    public static function toArray(array $row, $replaceFlag = ['*'], $targetFlag = '')
    {
        $data = [];
        foreach ($row as $key => $value) {
            $rowKey = str_replace($replaceFlag, $targetFlag, $key);

            // can be call after handleCalculateSheet, will auto calcute cell value
            // this line is fallback if not call Excel::handleCalculateSheet($event)
            $newValue = preg_replace("/=\"(.*)\"/", '\1', $value);

            $data[$rowKey] = $newValue ?: null;
        }

        return $data;
    }

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
    
    public static function valueToCellString($format = '="%s"', ...$value)
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
