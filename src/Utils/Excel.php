<?php

namespace ZhenMu\Support\Utils;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\Event;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Excel
{
    /**
     * 获取 Worksheet
     *
     * @param  Event     $event
     * @return Worksheet
     */
    public static function getSheet(Event $event): Worksheet
    {
        $sheet = $event->sheet->getDelegate();

        return $sheet;
    }

    /**
     * 处理导入数据的计算属性
     * 
     * call in:
     *      public static function beforeSheet(BeforeSheet $event)
     *
     * @param  Event $event
     * @return void
     */
    public static function handleCalculateSheet(Event $event)
    {
        $sheet = Excel::getSheet($event);

        ['row' => $maxRow, 'column' => $maxColName] = $sheet->getHighestRowAndColumn();

        // A=65
        $maxCol = ord($maxColName) - 64;
        $maxColName = chr($maxCol + 65);

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

    /**
     * 处理替换表头中的 *，兼容处理含计算属性的单元格
     * 
     * call in:
     *      public static function beforeSheet(BeforeSheet $event)
     *
     * @param  array  $row
     * @param  array  $replaceFlag
     * @param  string $targetFlag
     * @return array
     */
    public static function toArray(array $row, $replaceFlag = ['*'], $targetFlag = ''): array
    {
        $data = [];
        foreach ($row as $key => $value) {
            $rowKey = str_replace($replaceFlag, $targetFlag, $key);

            // can be call after handleCalculateSheet, will auto calcute cell value
            // this line is fallback if not call Excel::handleCalculateSheet($event)
            $newValue = preg_replace("/=\"(.*)\"/", '\1', $value);
            if (!empty($newValue)) {
                $newValue = trim($newValue);
            }

            $data[$rowKey] = $newValue ?: null;
        }

        return $data;
    }

    /**
     * 加载导入表格的 datetime 数据
     * 
     * call in:
     *      public static function beforeSheet(BeforeSheet $event)
     *
     * @param  string|null|mixed $datetime
     * @param  string $format
     * @param  string $soruceFormat
     * @return void
     */
    public static function datetimeFromCell(mixed $datetime = null, $format = 'Y-m-d H:i:s', $soruceFormat = 'Y-m-d H:i:s')
    {
        if (!$datetime) {
            return null;
        }

        if (is_string($datetime)) {
            $datetime = match (true) {
                default => null,
                str_contains($datetime, '-') && str_contains($datetime, ':') => Carbon::createFromFormat($soruceFormat, $datetime)->format($format),
                str_contains($datetime, '/') && !str_contains($datetime, ':') => Carbon::createFromDate($datetime)->format($format),
            };

            return $datetime;
        }

        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($datetime)?->format($format);
    }

    /**
     * 带 * 单元格红色标记
     * 带 :// 添加超链接
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event $event
     * @return void
     */
    public static function handleRequireCellTextColorForRed(Event $event)
    {
        $sheet = Excel::getSheet($event);

        ['row' => $maxRow, 'column' => $maxColName] = $sheet->getHighestRowAndColumn();

        // A=65
        $maxCol = ord($maxColName) - 64;
        $maxColName = chr($maxCol + 65);

        foreach (range(0, $maxRow) as $row) {
            foreach (range(0, $maxCol) as $col) {
                $columnLetter = chr($col + 65);
                $cell = "{$columnLetter}{$row}";

                // 设置列宽 autoSize
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);

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

                $newValue = $sheet->getCell($cell)->getValue();

                if (str_contains($newValue ?? '', '*')) {
                    $sheet->getStyle($cell)->getFont()->getColor()->setARGB(Color::COLOR_RED);
                }

                if (str_contains($newValue ?? '', '://')) {
                    Excel::cellAddHyperLink($event, $cell);
                }

                $sheet->getCell($cell)->setValue($newValue);
            }
        }

        info("最大单元格为 {$cell}, 最大列: {$maxColName} 最大行号: {$maxRow}");
    }

    /**
     * 从数组加载数据数据
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event   $event
     * @param  array   $data
     * @param  string  $startColumn
     * @param  integer $startRow
     * @return void
     */
    public static function loadDataFromArray(Event $event, array $data = [], string $startColumn = 'A', int $startRow = 1)
    {
        $sheet = Excel::getSheet($event);

        if ($startRow < 1) {
            $startRow = 1;
        }

        // startColumn
        $startColumnNum = ord($startColumn);

        foreach ($data as $dataRow => $item) {
            if (count($item) < 1) {
                continue;
            }

            // cellRow
            $cellRow = $dataRow + $startRow;

            foreach (range(0, count($item) - 1) as $dataCol) {
                $value = $item[$dataCol] ?? null;

                // A=65
                $columnName = chr($startColumnNum + $dataCol);

                $cell = "{$columnName}{$cellRow}";
                $sheet->setCellValue($cell, $value);
            }
        }
    }

    /**
     * 给指定列添加超链接
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event  $event
     * @param  string $columnLetter
     * @param  string $tooltip
     * @return void
     */
    public static function hyper(Event $event, string $columnLetter, $tooltip = "查看")
    {
        $sheet = Excel::getSheet($event);

        foreach ($sheet->getColumnIterator($columnLetter) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();

                if (str_contains($value, '://')) {
                    $cell->setHyperlink(new Hyperlink($value, $tooltip));

                    Excel::cellAddColor($event, $cell);
                }
            }
        }
    }

    /**
     * 给指定单元格添加超链接
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event  $event
     * @param  string $cell
     * @param  string $tooltip
     * @return void
     */
    public static function cellAddHyperLink(Event $event, string $cell, $tooltip = "查看")
    {
        $sheet = Excel::getSheet($event);

        $sheetCell = $sheet->getCell($cell);

        $value = $sheetCell->getValue();

        if (str_contains($value, '://')) {
            $sheetCell->setHyperlink(new Hyperlink($value, $tooltip));

            Excel::cellAddColor($event, $sheetCell->getCoordinate(), Color::COLOR_BLUE);
        }
    }

    /**
     * 给指定单元格添加颜色
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event  $event
     * @param  string $cell
     * @param  [type] $color
     * @return void
     */
    public static function cellAddColor(Event $event, string $cell, $color = Color::COLOR_BLACK)
    {
        $sheet = Excel::getSheet($event);

        $sheetCell = $sheet->getCell($cell);

        $sheetCell->getStyle()->getFont()->getColor()->setARGB($color);
    }

    /**
     * 合并单元格
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event  $event
     * @param  string $range
     * @param  string $behaviour
     * @return void
     */
    public static function mergeCells(Event $event, string $range, string $behaviour = Worksheet::MERGE_CELL_CONTENT_EMPTY)
    {
        $sheet = Excel::getSheet($event);

        $sheet->mergeCells($range, $behaviour);
    }

    /**
     * 设置表头加粗居中
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event   $event
     * @param  string  $cellOrRange
     * @param  array   $styleArray
     * @param  boolean $advancedBorders
     * @return void
     */
    public static function setTitleStyle(Event $event, string $cellOrRange, array $styleArray = [], $advancedBorders = true)
    {
        return Excel::setCellStyle(...func_get_args());
    }

    /**
     * 设置单元格样式，默认 14号字体，加粗居中
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event   $event
     * @param  string  $cellOrRange
     * @param  array   $styleArray
     * @param  boolean $advancedBorders
     * @return void
     */
    public static function setCellStyle(Event $event, string $cellOrRange, array $styleArray = [], $advancedBorders = true)
    {
        $sheet = Excel::getSheet($event);

        $titleStyle = array_merge([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ], $styleArray);

        $sheet->getStyle($cellOrRange)->applyFromArray($titleStyle, $advancedBorders);
    }

    /**
     * 单元格数字转文字显示
     * 
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *      public function collection(Collection $collection)
     *      public function array(): array
     *      public function map($row): array
     *      public function model(Collection $model)
     *
     * @param  string $format
     * @param  string|array ...$value
     * @return string
     */
    public static function valueToCellString($format = '="%s"', ...$value): string
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
