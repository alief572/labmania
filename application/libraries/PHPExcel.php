<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PHPExcel Compatibility Adapter for PHP 8.5
 * Bridges legacy PHPExcel API to modern PhpOffice\PhpSpreadsheet (5.x / 1.x)
 */

if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    if (file_exists(FCPATH . 'vendor/autoload.php')) {
        require_once FCPATH . 'vendor/autoload.php';
    } elseif (file_exists(APPPATH . 'vendor/autoload.php')) {
        require_once APPPATH . 'vendor/autoload.php';
    }
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

if (!class_exists('PHPExcel_Style_Alignment', false)) {
    class PHPExcel_Style_Alignment {
        const HORIZONTAL_GENERAL = 'general';
        const HORIZONTAL_LEFT = 'left';
        const HORIZONTAL_RIGHT = 'right';
        const HORIZONTAL_CENTER = 'center';
        const HORIZONTAL_CENTER_CONTINUOUS = 'centerContinuous';
        const HORIZONTAL_JUSTIFY = 'justify';
        const VERTICAL_BOTTOM = 'bottom';
        const VERTICAL_TOP = 'top';
        const VERTICAL_CENTER = 'center';
        const VERTICAL_JUSTIFY = 'justify';
    }
}

if (!class_exists('PHPExcel_Style_Border', false)) {
    class PHPExcel_Style_Border {
        const BORDER_NONE = 'none';
        const BORDER_THIN = 'thin';
        const BORDER_MEDIUM = 'medium';
        const BORDER_DASHED = 'dashed';
        const BORDER_DOTTED = 'dotted';
        const BORDER_THICK = 'thick';
        const BORDER_DOUBLE = 'double';
        const BORDER_HAIR = 'hair';
        const BORDER_MEDIUMDASHED = 'mediumDashed';
        const BORDER_DASHDOT = 'dashDot';
        const BORDER_MEDIUMDASHDOT = 'mediumDashDot';
        const BORDER_DASHDOTDOT = 'dashDotDot';
        const BORDER_MEDIUMDASHDOTDOT = 'mediumDashDotDot';
        const BORDER_SLANTDASHDOT = 'slantDashDot';
    }
}

if (!class_exists('PHPExcel_Style_Fill', false)) {
    class PHPExcel_Style_Fill {
        const FILL_NONE = 'none';
        const FILL_SOLID = 'solid';
        const FILL_GRADIENT_LINEAR = 'linear';
        const FILL_GRADIENT_PATH = 'path';
        const FILL_PATTERN_DARKDOWN = 'darkDown';
        const FILL_PATTERN_DARKGRAY = 'darkGray';
        const FILL_PATTERN_DARKGRID = 'darkGrid';
        const FILL_PATTERN_DARKHORIZONTAL = 'darkHorizontal';
        const FILL_PATTERN_DARKTRELLIS = 'darkTrellis';
        const FILL_PATTERN_DARKUP = 'darkUp';
        const FILL_PATTERN_DARKVERTICAL = 'darkVertical';
        const FILL_PATTERN_GRAY0625 = 'gray0625';
        const FILL_PATTERN_GRAY125 = 'gray125';
        const FILL_PATTERN_LIGHTDOWN = 'lightDown';
        const FILL_PATTERN_LIGHTGRAY = 'lightGray';
        const FILL_PATTERN_LIGHTGRID = 'lightGrid';
        const FILL_PATTERN_LIGHTHORIZONTAL = 'lightHorizontal';
        const FILL_PATTERN_LIGHTTRELLIS = 'lightTrellis';
        const FILL_PATTERN_LIGHTUP = 'lightUp';
        const FILL_PATTERN_LIGHTVERTICAL = 'lightVertical';
        const FILL_PATTERN_MEDIUMGRAY = 'mediumGray';
    }
}

if (!class_exists('PHPExcel_Style_NumberFormat', false)) {
    class PHPExcel_Style_NumberFormat {
        const FORMAT_GENERAL = 'General';
        const FORMAT_TEXT = '@';
        const FORMAT_NUMBER = '0';
        const FORMAT_NUMBER_00 = '0.00';
        const FORMAT_NUMBER_COMMA_SEPARATED1 = '#,##0.00';
        const FORMAT_NUMBER_COMMA_SEPARATED2 = '#,##0.00_-';
        const FORMAT_PERCENTAGE = '0%';
        const FORMAT_PERCENTAGE_00 = '0.00%';
        const FORMAT_DATE_YYYYMMDD2 = 'yyyy-mm-dd';
        const FORMAT_DATE_YYYYMMDD = 'yy-mm-dd';
        const FORMAT_DATE_DDMMYYYY = 'dd/mm/yy';
        const FORMAT_DATE_DMYSLASH = 'd/m/y';
        const FORMAT_DATE_DMYMINUS = 'd-m-y';
        const FORMAT_DATE_DMMYMINUS = 'd-m-yy';
        const FORMAT_DATE_MYMINUS = 'm-yy';
        const FORMAT_DATE_XLSX14 = 'mm-dd-yy';
        const FORMAT_DATE_XLSX15 = 'd-mmm-yy';
        const FORMAT_DATE_XLSX16 = 'd-mmm';
        const FORMAT_DATE_XLSX17 = 'mmm-yy';
        const FORMAT_DATE_XLSX22 = 'm/d/yy h:mm';
        const FORMAT_DATE_DATETIME = 'd/m/y h:mm';
        const FORMAT_DATE_TIME1 = 'h:mm AM/PM';
        const FORMAT_DATE_TIME2 = 'h:mm:ss AM/PM';
        const FORMAT_DATE_TIME3 = 'h:mm';
        const FORMAT_DATE_TIME4 = 'h:mm:ss';
        const FORMAT_DATE_TIME5 = 'd/m/y h:mm';
        const FORMAT_DATE_TIME6 = 'h:mm:ss';
        const FORMAT_DATE_TIME7 = 'i:s.S';
        const FORMAT_DATE_TIME8 = 'h:i:s';
        const FORMAT_DATE_YYYYMMDDSLASH = 'yy/mm/dd;@';
        const FORMAT_CURRENCY_USD_SIMPLE = '"$"#,##0.00_-';
        const FORMAT_CURRENCY_USD = '$#,##0_-';
        const FORMAT_CURRENCY_EUR_SIMPLE = '[$EUR ]#,##0.00_-';
    }
}

if (!class_exists('PHPExcel_Cell_DataType', false)) {
    class PHPExcel_Cell_DataType {
        const TYPE_STRING2 = 'str';
        const TYPE_STRING = 's';
        const TYPE_FORMULA = 'f';
        const TYPE_NUMERIC = 'n';
        const TYPE_BOOL = 'b';
        const TYPE_NULL = 'null';
        const TYPE_INLINE = 'inlineStr';
        const TYPE_ERROR = 'e';
    }
}

if (!function_exists('phpexcel_normalize_style_array')) {
    function phpexcel_normalize_style_array(array $style) {
        $normalized = [];
        foreach ($style as $k => $v) {
            $kLower = strtolower($k);
            if ($kLower === 'borders' && is_array($v)) {
                $borders = [];
                foreach ($v as $bk => $bv) {
                    $bkLower = strtolower($bk);
                    $targetKey = ($bkLower === 'allborders') ? 'allBorders' : $bk;
                    if (is_array($bv)) {
                        $borderItem = [];
                        foreach ($bv as $bik => $biv) {
                            $bikLower = strtolower($bik);
                            $itemKey = ($bikLower === 'style') ? 'borderStyle' : $bik;
                            $borderItem[$itemKey] = $biv;
                        }
                        $borders[$targetKey] = $borderItem;
                    } else {
                        $borders[$targetKey] = $bv;
                    }
                }
                $normalized['borders'] = $borders;
            } elseif ($kLower === 'fill' && is_array($v)) {
                $fill = [];
                foreach ($v as $fk => $fv) {
                    $fkLower = strtolower($fk);
                    $targetKey = ($fkLower === 'type') ? 'fillType' : $fk;
                    $fill[$targetKey] = $fv;
                }
                $normalized['fill'] = $fill;
            } elseif (is_array($v)) {
                $normalized[$k] = phpexcel_normalize_style_array($v);
            } else {
                $normalized[$k] = $v;
            }
        }
        return $normalized;
    }
}

if (!class_exists('PHPExcel_Style_Wrapper', false)) {
    #[\AllowDynamicProperties]
    class PHPExcel_Style_Wrapper {
        protected $realStyle;
        public function __construct($realStyle) {
            $this->realStyle = $realStyle;
        }
        public function applyFromArray(array $pStyles, $pAdvanced = true) {
            $norm = phpexcel_normalize_style_array($pStyles);
            $this->realStyle->applyFromArray($norm, $pAdvanced);
            return $this;
        }
        public function __call($name, $args) {
            $res = $this->realStyle->$name(...$args);
            return ($res instanceof \PhpOffice\PhpSpreadsheet\Style\Style) ? new self($res) : $res;
        }
    }
}

if (!class_exists('PHPExcel_Worksheet_Wrapper', false)) {
    #[\AllowDynamicProperties]
    class PHPExcel_Worksheet_Wrapper {
        protected $sheet;
        public function __construct(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
            $this->sheet = $sheet;
        }
        public function getRealSheet() {
            return $this->sheet;
        }
        public function getStyle($pCellCoordinate = 'A1') {
            return new PHPExcel_Style_Wrapper($this->sheet->getStyle($pCellCoordinate));
        }
        public function getStyleByColumnAndRow($pColumn = 0, $pRow = 1) {
            $colLetter = Coordinate::stringFromColumnIndex($pColumn + 1);
            return $this->getStyle($colLetter . $pRow);
        }
        public function setCellValueByColumnAndRow($pColumn = 0, $pRow = 1, $pValue = null) {
            $this->sheet->setCellValue([$pColumn + 1, $pRow], $pValue);
            return $this;
        }
        public function setCellValueExplicitByColumnAndRow($pColumn = 0, $pRow = 1, $pValue = null, $pDataType = null) {
            $colLetter = Coordinate::stringFromColumnIndex($pColumn + 1);
            if ($pDataType !== null) {
                $this->sheet->getCell($colLetter . $pRow)->setValueExplicit($pValue, $pDataType);
            } else {
                $this->sheet->setCellValue([$pColumn + 1, $pRow], $pValue);
            }
            return $this;
        }
        public function getCellByColumnAndRow($pColumn = 0, $pRow = 1) {
            return $this->sheet->getCell([$pColumn + 1, $pRow]);
        }
        public function mergeCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 1, $pColumn2 = 0, $pRow2 = 1) {
            $c1 = Coordinate::stringFromColumnIndex($pColumn1 + 1) . $pRow1;
            $c2 = Coordinate::stringFromColumnIndex($pColumn2 + 1) . $pRow2;
            $this->sheet->mergeCells("$c1:$c2");
            return $this;
        }
        public function unmergeCellsByColumnAndRow($pColumn1 = 0, $pRow1 = 1, $pColumn2 = 0, $pRow2 = 1) {
            $c1 = Coordinate::stringFromColumnIndex($pColumn1 + 1) . $pRow1;
            $c2 = Coordinate::stringFromColumnIndex($pColumn2 + 1) . $pRow2;
            $this->sheet->unmergeCells("$c1:$c2");
            return $this;
        }
        public function __call($name, $args) {
            return $this->sheet->$name(...$args);
        }
    }
}

if (!class_exists('PHPExcel', false)) {
    #[\AllowDynamicProperties]
    class PHPExcel {
        protected $spreadsheet;
        public function __construct() {
            $this->spreadsheet = new Spreadsheet();
        }
        public function getSpreadsheet() {
            return $this->spreadsheet;
        }
        public function getActiveSheet() {
            return new PHPExcel_Worksheet_Wrapper($this->spreadsheet->getActiveSheet());
        }
        public function setActiveSheetIndex($pIndex = 0) {
            $this->spreadsheet->setActiveSheetIndex($pIndex);
            return $this->getActiveSheet();
        }
        public function getActiveSheetIndex() {
            return $this->spreadsheet->getActiveSheetIndex();
        }
        public function createSheet($pIndex = null) {
            $sheet = $this->spreadsheet->createSheet($pIndex);
            return new PHPExcel_Worksheet_Wrapper($sheet);
        }
        public function getSheetCount() {
            return $this->spreadsheet->getSheetCount();
        }
        public function getSheet($pIndex = 0) {
            return new PHPExcel_Worksheet_Wrapper($this->spreadsheet->getSheet($pIndex));
        }
        public function getSheetNames() {
            return $this->spreadsheet->getSheetNames();
        }
        public function removeSheetByIndex($pIndex = 0) {
            $this->spreadsheet->removeSheetByIndex($pIndex);
            return $this;
        }
        public function getDefaultStyle() {
            return new PHPExcel_Style_Wrapper($this->spreadsheet->getDefaultStyle());
        }
        public function getProperties() {
            return $this->spreadsheet->getProperties();
        }
        public function __call($name, $args) {
            return $this->spreadsheet->$name(...$args);
        }
    }
}

if (!class_exists('PHPExcel_IOFactory', false)) {
    class PHPExcel_IOFactory {
        public static function createWriter($phpExcel, $writerType = '') {
            $spreadsheet = ($phpExcel instanceof PHPExcel) ? $phpExcel->getSpreadsheet() : $phpExcel;
            $wType = strtoupper($writerType);
            if ($wType === 'EXCEL5') {
                $wType = 'Xls';
            } elseif ($wType === 'EXCEL2007') {
                $wType = 'Xlsx';
            } elseif ($wType === 'CSV') {
                $wType = 'Csv';
            }
            return IOFactory::createWriter($spreadsheet, $wType);
        }

        public static function load($pFilename) {
            $spreadsheet = IOFactory::load($pFilename);
            $adapter = new PHPExcel();
            // Reflection to assign loaded spreadsheet
            $ref = new \ReflectionProperty(PHPExcel::class, 'spreadsheet');
            $ref->setAccessible(true);
            $ref->setValue($adapter, $spreadsheet);
            return $adapter;
        }

        public static function createReader($readerType = '') {
            $rType = strtoupper($readerType);
            if ($rType === 'EXCEL5') {
                $rType = 'Xls';
            } elseif ($rType === 'EXCEL2007') {
                $rType = 'Xlsx';
            } elseif ($rType === 'CSV') {
                $rType = 'Csv';
            }
            return IOFactory::createReader($rType);
        }
    }
}
