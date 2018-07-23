<?php

/*Подключаем генератор PDF*/
require_once './php/fpdf/fpdf.php';

class PDF extends FPDF {
    public static $padding = 10;/*По умолчанию поля равны 1 см и это нормально - нет смысла изменять*/
    public static $imgPadding = 10;
    public static $logoSize = 15;
    public static $footerIconSize = 7;
    public static $footerFontSize = 10;
    public static $headerFontSize = 10;
    public static $standFontSize = 14;
    public static $orientation = 'P';
    private static $pageSize = 'A4';
    private static $fontName = 'Times';
    /*
     * Коэффициент для расчета ширины миниатюр.
     * Указывается в десятичной дроби от ширины области печати миниатюр,
     * которая составляет половину рабочей ширины страницы
     */
    private static $thumbImgWidth = .2;
    /*
     * Отступ между миниатюрами по вертикали и горизонтали
     */
    private static $thumbPaddingV = 5;
    private static $thumbPaddingH = 5;
    /*Параметры листов*/
    private $PDFPages = array(
        //'PageIndex' => 'PDFPage instance'
    );
    private $PDFPage = null;
    
    public function __construct() {
        parent::__construct();
        $this->AddPage(self::$orientation, self::$pageSize);
    }
    
    function Header() {
        /*Создаем заголовок документа*/
        $this->Image('./pict/logotype.png', self::$padding, self::$padding, self::$logoSize, self::$logoSize);
        $this->SetFont(self::$fontName, '', self::$headerFontSize);
        $x = self::$padding + self::$logoSize + self::$imgPadding;
        $y = self::$padding + self::PointsToMM(self::$headerFontSize) + 3;
        $this->Text($x, $y, 'US Global Motors Corp. Exporting & Logistics Worldwide');
        $y = self::PointsToMM(self::$headerFontSize) * 2 + self::$padding + 8;
        $this->Text($x, $y, 'CFO/Financial Director Andrey Gordienko');
        $_y = self::$padding + self::$logoSize + self::$imgPadding;
        $this->top = $_y > $y ? $_y : $y;
        $this->topColontitleHeight = $this->top;
        /*Создаем объект, который будет хранить параметры листа*/
        $page = new PDFPage($this);
        $this->PDFPages[] = $page;
        $this->PDFPage = $page;
        $page->top = $_y > $y ? $_y : $y;
        $page->left = self::$padding;
        $page->topColontitleHeight = $page->top;
        /*Высоту нижнего колонтитула будем считать не превышающей размера иконки*/
        $page->bottomColontitleHeight = self::$footerIconSize;
        $page->workAreaTop = $page->top;
        $page->workAreaBottom = $this->GetPageHeight() - self::$padding - self::$footerIconSize - 5;
        $page->workAreaLeft = self::$padding;
        $page->workAreaRight = $this->GetPageWidth() - self::$padding;
        $page->workAreaWidth = $this->GetPageWidth() - self::$padding * 2;
        $page->workAreaHeight = $this->GetPageHeight() - self::$padding * 2 - $page->topColontitleHeight - $page->bottomColontitleHeight;
    }
    
    function Footer() {
        $this->SetFont(self::$fontName, '', self::$footerFontSize);
        $y = $this->getPageHeight() - self::$padding - self::$footerIconSize;
        $this->Image('./pict/web.png', self::$padding, $y, self::$footerIconSize, self::$footerIconSize);
        $this->Image('./pict/phone.png', self::$padding + intdiv($this->getPageWidth(), 2), $y, self::$footerIconSize, self::$footerIconSize);
        
        $y = $this->getPageHeight() - self::$padding - self::$footerIconSize / 3;
        $x = self::$padding + self::$footerIconSize + self::$imgPadding / 2;
        $this->Text($x, $y, 'www.usglobalmotors.com');
        
        $x = self::$padding + intdiv($this->getPageWidth(), 2) + self::$footerIconSize + self::$imgPadding / 2;
        $this->Text($x, $y, '+79161875227');
    }
    
    function printLotHeader($text) {
        $text = iconv('utf-8', 'cp1251', $text);
        $fontSize = self::$standFontSize * 1.5;
        $this->SetFont(self::$fontName, '', $fontSize);
        $maxWidth = $this->PDFPage->workAreaRight;
        $x = $this->PDFPage->left;
        $y = $this->PDFPage->top + self::PointsToMM($fontSize) + 6;
        for($i = 0; $i < strlen($text); ++$i) {
            $char = $text[$i];
            $wChar = $this->GetStringWidth($char);
            
            if ($x + $wChar >= $maxWidth) {
                $x = $this->PDFPage->left;
                $y += self::PointsToMM($fontSize) + 6;
            }
            $this->Text($x, $y, $char);
            $x += $wChar;
        }
        /*После печати заголовка запомним новые координаты для ординаты*/
        $this->PDFPage->top = $y + 6;
    }
    
    function printImages($images) {
        
        if (
                !$images ||
                gettype($images) !== gettype(array()) ||
                count($images) < 1
            )
        {
            /*Нет изображений - нечего и печатать*/
            return false;
        }
        /*
         * Печать параметров начинается с самого первого листа
         */
        $this->setPage(0);
        /*Сначала получим главное изображение*/
        $mainImg = (new linq($images))->first(function($img){
            return $img['IsMain'] === true;
        });
        !$mainImg && ($mainImg = $images[0]);
        
        
        /*Определим параметры для печати главного изображения*/
        $printW = $this->PDFPage->getCenter('x') - $this->PDFPage->workAreaLeft - 2;
        $printH = floor($mainImg['Height'] * ($printW / $mainImg['Width']));
        $maxX = $this->PDFPage->getCenter('x') - 2;
        
        $this->Image(
            $mainImg['FileName'], 
            $this->PDFPage->left, 
            $this->PDFPage->top, 
            $printW, 
            $printH
        );
        
        $y = $this->PDFPage->top + $printH + 2 * self::$thumbPaddingV;
        $x = $this->PDFPage->left;
        
        $thumbMaxH = 0;
        
        $thumbW = floor($printW * self::$thumbImgWidth);
        
        for ($i = 0; $i < count($images); ++$i) {
            $img = $images[$i];
            if ($img['IdImage'] === $mainImg['IdImage']) {
                continue;
            }
            
            $thumbH = floor($img['Height'] * ($thumbW / $img['Width']));
            $thumbMaxH = $thumbH > $thumbMaxH ? $thumbH : $thumbMaxH;
            
            if ($x + $thumbW + self::$thumbPaddingH > $maxX) {
                /*В данной строке изображение уже не поместится - переходим на новую строку*/
                $y += $thumbMaxH + self::$thumbPaddingV;
                /*А абсциссу ставим на начало строки*/
                $x = $this->PDFPage->workAreaLeft;
                /*Т.к. строка новая, то в ней еще не известна максимальная высота изображения*/
                $thumbMaxH = 0;
            }
            if ($y + $thumbH > $this->PDFPage->workAreaBottom) {
                /*Данная строка уже не помещается на листе - переходим на новый лист*/
                $nextIndex = $this->PDFPage->Index() + 1;
                if (array_key_exists($nextIndex, $this->PDFPages)) {
                    $this->setPage($nextIndex);
                }
                else {
                    $this->AddPage(self::$orientation, self::$pageSize);
                }
                /*При переходе на следующий лист координаты для печати ставим в верхний левый угол рабочей области страницы*/
                $y = $this->PDFPage->workAreaTop;
                $x = $this->PDFPage->workAreaLeft;
            }
            
            
            $this->Image(
                $img['FileName'], 
                $x, 
                $y, 
                $thumbW, 
                $thumbH
            );
            
            /*После печати очередного изображения сдвигаем абсциссу*/
            $x += $thumbW + self::$thumbPaddingH;
            
        }
        
        return true;
    }
    
    /*Функция получает соотношение сторон изображения для сохранения пропорций при печати*/
    private static function getImgRatio($img) {
        $ratio = 1;
        if ($img['Width'] !== 0 && $img['Height'] !== 0) {
            $ratio = $img['Width'] / $img['Height'];
        }
        return $ratio;
    }
    
    function printParams($params) {
        if (
                !$params ||
                gettype($params) !== gettype(array()) ||
                count($params) < 1
            )
        {
            /*Нет параметров - нечего и печатать*/
            return false;
        }
        /*
         * Печать параметров начинается с самого первого листа
         */
        
        $this->setPage(0);
        $pdf = $this;
        $fontSize = self::$standFontSize * .7;
        
        $this->SetFont(self::$fontName, '', $fontSize);
        /*Определим  начальную абсциссу левой колонки*/
        $leftColL = $this->PDFPage->getCenter('x') + 2;
        /*Определим максимальную ширину области печати параметров лота*/
        $maxWidth = $this->PDFPage->workAreaRight;
        /*Определим  конечную абсциссу левой колонки*/
        $leftColR = $leftColL + ceil(($maxWidth - $leftColL) / 2);
        /*Определим  начальную абсциссу правой колонки*/
        $rightColL = $leftColR + 10;
        /*Определим  конечную абсциссу левой колонки*/
        $rightColR = $this->PDFPage->workAreaRight;
        /*Определим интервал для строки*/
        $lineInterval = self::PointsToMM($fontSize) + 5;
        /*Определим максимальную нижнюю границу для размещения данных*/
        $maxBottom = $this->PDFPage->workAreaBottom - $lineInterval;
        
        $printChar = function($char, &$x, &$y, $maxWidth, $defX) use ($pdf, $fontSize, $lineInterval){
            $wChar = $pdf->GetStringWidth($char);
            
            if ($x + $wChar >= $maxWidth) {
                $x = $defX;
                $y += $lineInterval;
            }
            $this->Text($x, $y, $char);
            $x += $wChar;
            
        };
        
        $yVal = $this->PDFPage->top;
        $yCap = $this->PDFPage->top;
        
        foreach ($params as $k => $v) {
            $caption = $v['Caption'] ? $v['Caption'] : $v['Name'];
            $caption = iconv('utf-8', 'cp1251', $caption);
            if ($caption) {
                $x = $leftColL;
                for($i = 0; $i < strlen($caption); ++$i) {
                    $char = $caption[$i];
                    $printChar($char, $x, $yCap, $leftColR, $leftColL);
                }
            }

            $value = $v['Value'] ? iconv('utf-8', 'cp1251', $v['Value']) : null;
            if ($value) {
                $x = $rightColL;
                for($i = 0; $i < strlen($value); ++$i) {
                    $char = $value[$i];
                    $printChar($char, $x, $yVal, $rightColR, $rightColL);
                }
            }

            $yVal = ($yCap > $yVal ? $yCap : $yVal) +  $lineInterval;
            $yCap = $yVal;
            if ($yCap >= $maxBottom) {
                /*Переходим на новый лист*/
                $nextIndex = $this->PDFPage->Index() + 1;
                if (array_key_exists($nextIndex, $this->PDFPages)) {
                    $this->setPage($nextIndex);
                }
                else {
                    $this->AddPage(self::$orientation, self::$pageSize);
                }
                $yVal = $this->PDFPage->workAreaTop;
                $yCap = $this->PDFPage->workAreaTop;
            }
        }
        return true;
    }
    
    private static function PointsToMM($points) {
        /*1 point = 1/72 "*/
        return ceil($points / 72);
    }
    
    private function _setFillColor($fillColor) {
        if (!$fillColor) {
            return false;
        }
        $r = 255; $g = 255; $b = 255;
        if (gettype($fillColor) === gettype(array())) {
            $key = 'r';
            array_key_exists($key, $fillColor) && ($r = $fillColor[$key]);
            $key = 'R';
            array_key_exists($key, $fillColor) && ($r = $fillColor[$key]);
            $key = 'g';
            array_key_exists($key, $fillColor) && ($g = $fillColor[$key]);
            $key = 'G';
            array_key_exists($key, $fillColor) && ($g = $fillColor[$key]);
            $key = 'b';
            array_key_exists($key, $fillColor) && ($b = $fillColor[$key]);
            $key = 'B';
            array_key_exists($key, $fillColor) && ($b = $fillColor[$key]);
        }
        if ($r !== 255 || $b !== 255 || $g !== 255) {
            //echo 'setFillColor ' . $r . ' ' . $g . ' ' . $b . ' ';
            $this->SetFillColor($r, $g, $b);
            return true;
        }
    }
    
    public function setPage($index) {
        /*
         * Данный метод заведомо не будет вызываться, если еще ни одна страница 
         * не была добавлена в документ, поэтому не проверяем наличие страниц
         */
        $index = $index < 0 ? 0 : $index >= count($this->PDFPages) ? count($this->PDFPages) : $index;
        $this->page = $index + 1;
        $this->PDFPage = $this->PDFPages[$index];
    }
    
    public function pageExists($index) {
        return $index >= 0 && array_key_exists($index, $this->PDFPages);
    }
    
    
}

class PDFPage {
    public $top = 0; // => 'текущая ордината на листе',
    public $left = 0; // => 'текущая абсцисса на листе',
    public $topColontitleHeight = 0; // => 'Высота верхнего колонтитула',
    public $bottomColontitleHeight = 0; // => 'Высота нижнего колонтитула',
    public $workAreaTop = 0;// => 'Верхняя граница рабочей области листа',
    public $workAreaBottom = 0;// => 'Нижняя граница рабочей области листа',
    public $workAreaLeft = 0;// => 'Левая граница рабочей области листа',
    public $workAreaRight = 0;// => 'Правая граница рабочей области листа',
    public $workAreaWidth = 0;// => 'Ширина рабочей области листа',
    public $workAreaHeight = 0;// => 'Высота рабочей области листа',
    private $pdf;//Связь с экземпляром родителського PDF
    /*Индексный номер листа*/
    private $index;
    public function __construct($pdf) {
        $this->pdf = $pdf;
        $this->index = $this->pdf->PageNo();
    }
    public function Index () {
        return $this->index;
    }
//    private function _switchPdfToPageNo() {
//        $this->pdf->setPage($this->index);
//    }
//    public function Text($x, $y, $txt) {
//        $this->_switchPdfToPageNo();
//        $this->pdf->Text($x, $y, $txt);
//    }
//    public function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='') {
//        $this->_switchPdfToPageNo();
//        $this->pdf->Image($file, $x, $y, $w, $h, $type, $link);
//        
//    }
    public function getCenter($coord) {
        if (strtolower($coord) === 'y') {
            return floor($this->workAreaBottom - ($this->workAreaBottom - $this->workAreaTop) / 2);
        }
        else {
            return floor($this->workAreaRight - ($this->workAreaRight - $this->workAreaLeft) / 2);
        }
    }
}