<?php

namespace ZhenMu\Support\Utils;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\Event;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Excel
{
    public static function readChineseHeader()
    {
        config([
            'excel.imports.heading_row.formatter' => null,
        ]);
    }

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

    public static function getSheetMaxRowAndColumnInfo(Event $event): array
    {
        $sheet = Excel::getSheet($event);

        ['row' => $maxRow, 'column' => $maxColName] = $sheet->getHighestRowAndColumn();

        // maxRow, maxCol 从 1 开始
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxColName);
        // A=65
        $maxColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxCol);

        return [
            'maxRow' => $maxRow,
            'maxCol' => $maxCol,
            'maxColumnLetter' => $maxColumnLetter,
        ];
    }

    public static function getSheetCellNameByRowAndColumn(int $col, int $row)
    {
        $columnLetter = Excel::getSheetColumnLetter($col);

        $cell = "{$columnLetter}{$row}";

        return [
            'columnLetter' => $columnLetter,
            'cell' => $cell,
            'row' => $row,
        ];
    }

    public static function getSheetColumnLetter(int $col)
    {
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);

        return $columnLetter;
    }

    public static function handleAllCell(Event $event, callable $callable)
    {
        $sheet = Excel::getSheet($event);

        $sheetInfo = Excel::getSheetMaxRowAndColumnInfo($event);

        foreach (range(0, $sheetInfo['maxRow']) as $row) {
            foreach (range(1, $sheetInfo['maxCol']) as $col) {
                $cellInfo = Excel::getSheetCellNameByRowAndColumn($col, $row);

                $callable($event, $sheet, $sheetInfo, $cellInfo);
            }
        }

        $backTrace = debug_backtrace(2, 2);
        $callFunctionName = $backTrace[1]['function'];

        info(sprintf(
            "%s: 最大单元格为 %s, 最大列: %s 最大行号: %s",
            $callFunctionName,
            $cellInfo['cell'],
            $sheetInfo['maxCol'],
            $sheetInfo['maxRow']
        ));
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
        Excel::handleAllCell($event, function ($event, $sheet, $sheetInfo, $cellInfo) {
            try {
                $calcValue = $sheet->getCell($cellInfo['cell'])->getCalculatedValue();
                $newValue = $calcValue;
            } catch (\Throwable $e) {
                $value = $sheet->getCell($cellInfo['cell'])->getValue();

                info("获取单元格 {$cellInfo['cell']} 计算结果错误", [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'cell' => $cellInfo['cell'],
                    'origin_value' => $value,
                ]);

                $newValue = $value;
            }

            $sheet->getCell($cellInfo['cell'])->setValue($newValue);
        });
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

        // num: 兼容表头出现空的情况
        $num = 0;
        foreach ($row as $key => $value) {
            $rowKey = str_replace($replaceFlag, $targetFlag, $key);
            if ($rowKey !== false) {
                $rowKey = trim($rowKey);
                $rowKey = $rowKey ?: $num;
            }

            // can be call after handleCalculateSheet, will auto calcute cell value
            // this line is fallback if not call Excel::handleCalculateSheet($event)
            $newValue = preg_replace("/=\"(.*)\"/", '\1', $value);
            if (strlen($newValue) == 1 && str_contains($newValue, '-')) {
                $newValue = str_replace('-', '', $newValue);
            }

            if (!empty($newValue)) {
                $newValue = trim($newValue);
            }

            $data[$rowKey] = $newValue ?: null;

            $num++;
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

        if (Str::isPureInt($datetime)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($datetime)?->format($format);
        }

        $datetimeData = explode(' ', $datetime);

        $day = null;
        $time = null;
        if (count($datetimeData) == 2) {
            $day = $datetimeData[0];
            $time = $datetimeData[1];
        } else {
            $day = $datetimeData[0];
            $time = "";
        }

        $day = match (true) {
            default => null,
            str_contains($day, '-') => $datetime,
            str_contains($day, '.') => str_replace('.', '-', $day),
            str_contains($day, '/') => str_replace('/', '-', $day),
        };

        $datetime = $day;
        if ($time) {
            $datetime .= " " . $time;
        }

        if (!str_contains($datetime, ':')) {
            $datetime = Carbon::createFromDate($datetime)->format($format);
        } else {
            $datetime = Carbon::createFromFormat($soruceFormat, $datetime)->format($format);
        }

        return $datetime;
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
    public static function handleRequireCellTextColorForRedAndHyperLink(Event $event)
    {
        Excel::handleAllCell($event, function ($event, $sheet, $sheetInfo, $cellInfo) {
            $value = $sheet->getCell($cellInfo['cell'])->getValue();

            try {
                if (str_contains($value, "'")) {
                    $newValue = str_replace("'", '', $value);
                } else {
                    $calcValue = $sheet->getCell($cellInfo['cell'])->getCalculatedValue();
                    $newValue = $calcValue;
                }
            } catch (\Throwable $e) {
                info("获取单元格 {$cellInfo['cell']} 计算结果错误", [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'cell' => $cellInfo['cell'],
                    'origin_value' => $value,
                ]);

                $newValue = $value;
            }

            if (str_contains($newValue ?? '', '*')) {
                $sheet->getStyle($cellInfo['cell'])->getFont()->getColor()->setARGB(Color::COLOR_RED);
            }

            if (str_contains($newValue ?? '', '://')) {
                Excel::cellAddHyperLink($event, $cellInfo['cell']);
            }

            $sheet->getCell($cellInfo['cell'])->setValue($newValue);
        });
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
     * 设置单元格数据
     *
     * call in:
     *      public static function afterSheet(AfterSheet $event)
     *
     * @param  Event  $event
     * @param  string $cell
     * @param  mixed $value
     * @return void
     */
    public static function setCellValue(Event $event, string $cell, $value)
    {
        $sheet = Excel::getSheet($event);

        $sheetCell = $sheet->getCell($cell);

        $sheetCell->setValue($value);
    }

    public static function getListData(array $firstZipData, array ...$otherZipDatas)
    {
        $data = collect($firstZipData)->zip(...$otherZipDatas);

        return $data;
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
        $sheet = Excel::getSheet($event);

        // autoSize
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $rangeCellNames = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::extractAllCellReferencesInRange($cellOrRange);

        $styleArray = array_merge([
            'font' => [
                'bold' => true,
                'size' => 14,
                'name' => 'Microsoft YaHei',
                'color' => [
                    'argb' => 'ffffff',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'C00000',
                ],
            ],
        ], $styleArray);

        foreach ($rangeCellNames as $cell) {
            $value = $sheet->getCell($cell)->getValue();

            if (str_contains($value, '*')) {
                $styleArray['fill']['startColor']['argb'] = 'C00000';
                $styleArray['font']['color']['argb'] = 'ffffff';
            } else {
                $styleArray['fill']['startColor']['argb'] = '404040';
                $styleArray['font']['color']['argb'] = 'ffffff';
            }

            $sheet->getStyle($cell)->applyFromArray($styleArray, $advancedBorders);
        }
    }

    public static function setExplainData($event, $explain, $inCellRange, $height = 200, $advancedBorders = true)
    {
        $sheet = Excel::getSheet($event);

        // $explain = <<<"TXT"
        // 填写须知：
        // 1. 请勿修改表格结构；
        // 2. 红色字段为必填项，黑色字段为选填项；
        // 3. 用户 ID：非必填，成员的唯一标识，可以由字母、数字、‘_-@.’符号组成，不填则由系统自动生成；
        // 4. 姓名：默认名称，必填，如需设置中文、英语、日语名称，可按如下规则填写：默认名称 | CN-中文名 | EN-英语名 | JP-日语名，例如“张三 | CN-张三 | EN-ZhangSan | JP-張三”；
        // 5. 联系手机：必填，且在本企业内不可重复，地区码必须包含加号 +，为保证可以正常编辑带地区码格式的国际手机号，建议将手机号一列的单元格格式调整为“文本”；
        // 6. 部门：必填，上下级部门间使用“/”隔开，请从最上级部门（即企业名称）开始填写。例如“飞书有限公司/研发部""，若未填写则默认添加到选择的节点下，若归属于多个部门请用英文“,”隔开；请注意部门顺序，其中第一个部门将在导入后被标记为主部门，其余顺序也将在个人信息中体现；
        // 7. 性别：请填写男或女；
        // 8. 直属上级：请填写直属上级的手机号（若为国际手机号则必须包含加号以及国家地区码，例如：“+8589****33”）、邮箱或用户 ID，如匹配失败请检查是否填对字段或直属上级是否未导入通讯录；
        // 9. 人员类型：必填，请从下列选项中选填一个选项：“正式”、“实习”、“外包”、“劳务”、“顾问”，若不填则默认为“正式”；
        // 10. 部门负责人：请填入“是”、“否”，若不填则默认为“否”，若归属于多个部门请用英文“,”隔开；
        // 11. 入职日期：必填，请按 YYYY-MM-DD 的格式填写，如 2019-01-01；
        // 12. 手机号是否可见：请填入“是”、“否”，若不填则默认为“是”；
        // 13. 工作城市：请先在“成员字段管理”中为“工作城市”添加选项，目前可从下列选项中选填一项：请先在“成员字段管理”中为“工作城市”添加选项并设置为“已启用”状态
        // 14. 职务：请先在“成员字段管理”中为“职务”添加选项，目前可从下列选项中选填一项：请先在“成员字段管理”中为“职务”添加选项并设置为“已启用”状态
        // 15. URL 类自定义字段：非必填，所有字段名后带“(URL)”的字段均为 URL 类自定义字段，可以超链接形式展示在客户端个人名片页。若需填写信息，则需按“标题 | URL | URL”格式填写，其中标题必填且至少需填写一个 URL，若只填写 1 个 URL 则移动端和桌面端共用该URL，若填写了 2 个 URL 则默认第一个为移动端 URL，URL 请以“http://”或“https://”开头。														
        // TXT;

        $sheet->getCell('A1')->setValue($explain);
        $sheet->getCell('A1')->setHyperlink(null);

        // $sheet->getColumnDimension('A')->setWidth(300); // 设置宽度
        $sheet->getRowDimension(1)->setRowHeight($height);

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'size' => 12,
                'name' => 'Microsoft YaHei',
                'color' => [
                    'argb' => '000000',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'alignment' => [
                // 'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ], $advancedBorders);

        Excel::mergeCells($event, $inCellRange);
    }

    public static function setTitleStyleAndExplainData($event, $explain, $explainMergeRange, $titleRange)
    {
        // 带 * 单元格红色标记
        Excel::handleRequireCellTextColorForRedAndHyperLink($event);
        // 表头加租居中
        Excel::setTitleStyle($event, $titleRange);
        // 设置说明
        Excel::setExplainData($event, $explain, $explainMergeRange);
    }

    public static function setListCell($event, $columnName, $dataStartCellNum, $sheetName, $startCellAndEndCell, $maxRowNum = 1000, $errorTitle = null, $errorMessage = null, $promptTitle = null, $promptMessage = null)
    {
        /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet */
        $spreadsheet = Excel::getSheet($event);

        $dataEndCellNum = $dataStartCellNum + $maxRowNum;
        foreach (range($dataStartCellNum, $dataEndCellNum) as $i) {
            $validation = $spreadsheet->getCell($columnName . $i)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            if ($errorTitle) {
                // $validation->setErrorTitle('Input error');
                $validation->setErrorTitle('Input error');
            }
            if ($errorMessage) {
                // $validation->setError('Value is not in list.');
                $validation->setError($errorMessage);
            }
            if ($promptTitle) {
                // $validation->setPromptTitle('Pick from list');
                $validation->setPromptTitle($promptTitle);
            }
            if ($promptMessage) {
                // $validation->setPrompt('Please pick a value from the drop-down list.');
                $validation->setPrompt($promptMessage);
            }

            $validation->setFormula1(<<<EOL
            =INDIRECT("{$sheetName}!{$startCellAndEndCell}")
            EOL);
        }
    }

    /**
     * 设置单元格样式，默认 14号字体，不加粗居中
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

        $styleArray = array_merge([
            'font' => [
                'color' => [
                    'argb' => '000000',
                ],
                'bold' => false,
                'size' => 12,
                'name' => '微软雅黑',
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            // 'fill' => [
            //     'fillType' => Fill::FILL_SOLID,
            //     'startColor' => [
            //         'argb' => 'C00000',
            //     ],
            // ],
        ], $styleArray);

        $sheet->getStyle($cellOrRange)->applyFromArray($styleArray, $advancedBorders);
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
     * @return string|null
     */
    public static function valueToCellString($format = '="%s"', ...$value): ?string
    {
        if (!str_starts_with($format, '=')) {
            $value = $format;
            if (preg_match('/^\d+?$/', $format) == false) {
                return $value;
            }

            $format = '="%s"';

            if (!$value) {
                return null;
            }

            $value = [$value];
        }

        return sprintf($format, ...$value);
    }
}
