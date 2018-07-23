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
    public static function getPageSize() {
        return self::$pageSize;
    }
    private static $fontName = 'Times';
    public static function getFontName() {
        return self::$fontName;
    }
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
    public function getPDFPages() {
        return $this->PDFPages;
    }
    private $PDFPage = null;
    public function getPDFPage() {
        return $this->PDFPage;
    }
    /*
     * Режим набора страниц. 
     * inconsistent - означает, что после добавления новой страницы мы в любой 
     *      момент можем обратиться к одной из предыдущих , чтобы ее дополнить.
     *      Поэтому в таком режиме завершение страницы будет вызвано только 
     *      при завершении всего документа
     * consistent - означает, что после добавления новой страницы мы уже не можем 
     *      вернуться ни к одной из предыдущих для их дополнения
     */
    private $pageCollectingMode = 'inconsistent';
    
    public $numeratePages = true;
    
    public function __construct($addNewPage = true) {
        parent::__construct();
        $addNewPage && $this->AddPage(self::$orientation, self::$pageSize);
    }
    
    function AddPage($orientation='', $size='', $rotation=0)
    {
        // Start a new page
        if($this->state==3)
            $this->Error('The document is closed');
        $family = $this->FontFamily;
        $style = $this->FontStyle.($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        if($this->page>0 && strtolower($this->pageCollectingMode) !== 'inconsistent')
        {
            // Page footer
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            // Close page
            $this->_endpage();
        }
        // Start new page
        $this->_beginpage($orientation,$size,$rotation);
        // Set line cap style to square
        $this->_out('2 J');
        // Set line width
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w',$lw*$this->k));
        // Set font
        if($family)
            $this->SetFont($family,$style,$fontsize);
        // Set colors
        $this->DrawColor = $dc;
        if($dc!='0 G')
            $this->_out($dc);
        $this->FillColor = $fc;
        if($fc!='0 g')
            $this->_out($fc);
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        // Page header
        $this->InHeader = true;
        $this->Header();
        $this->InHeader = false;
        // Restore line width
        if($this->LineWidth!=$lw)
        {
            $this->LineWidth = $lw;
            $this->_out(sprintf('%.2F w',$lw*$this->k));
        }
        // Restore font
        if($family)
            $this->SetFont($family,$style,$fontsize);
        // Restore colors
        if($this->DrawColor!=$dc)
        {
            $this->DrawColor = $dc;
            $this->_out($dc);
        }
        if($this->FillColor!=$fc)
        {
            $this->FillColor = $fc;
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
    }
    
    function Close()
    {
        if (strtolower($this->pageCollectingMode) === 'inconsistent') {
            /*
             * В данном режиме у нас страницы при начале новой не завершались и нужно 
             * их завершить принудительно
             */
            $maxIndex = -1;
            foreach ($this->pages as $k => $page) {
                $this->page = $k;
                $this->Footer();
                $k > $maxIndex && ($maxIndex = $k);
            }
            
            /*
             * Чтобы родительский метод отработал правильно, нужно сменить режим
             * и выставить правильный $this->page
             */
            $this->page = $maxIndex;
            $this->pageCollectingMode = 'consistent';
        }
        parent::Close();
    }
    
    function Header() {
        /*Создаем заголовок документа*/
        $this->Image('./pict/logotype.png', self::$padding, self::$padding, self::$logoSize, self::$logoSize);
        $this->SetFont(self::$fontName, '', self::$headerFontSize);
        $x = self::$padding + self::$logoSize + self::$imgPadding;
        $y = self::$padding + self::PointsToMM(self::$headerFontSize);
        $this->Text($x, $y, 'US Global Motors Corp.');
        $y = self::PointsToMM(self::$headerFontSize) * 2 + self::$padding + 2;
        $this->Text($x, $y, 'Worldwide US Exporter');
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
        // $this->Image('./pict/web.png', self::$padding, $y, self::$footerIconSize, self::$footerIconSize);
        // $this->Image('./pict/phone.png', self::$padding + intdiv($this->getPageWidth(), 2), $y, self::$footerIconSize, self::$footerIconSize);
        
        $y = $this->getPageHeight() - self::$padding - self::$footerIconSize / 3;
        $x = self::$padding + self::$footerIconSize + self::$imgPadding / 2;
        $this->Text($x, $y, 'www.usglobalmotors.com');
        
        $x = self::$padding + intdiv($this->getPageWidth(), 2) + self::$footerIconSize + self::$imgPadding / 2;
        $this->Text($x, $y, 'info@usglobalmotors.com');
    }
    
    function printLotHeader($text) {
        $text = iconv('utf-8', 'cp1251', $text);
        $fontSize = self::$standFontSize * 1.5;
        $this->SetFont(self::$fontName, '', $fontSize);
        $maxWidth = $this->PDFPage->workAreaRight;
        $x = $this->PDFPage->left;
        $y = $this->PDFPage->top + self::PointsToMM($fontSize) + 2;
        for($i = 0; $i < strlen($text); ++$i) {
            $char = $text[$i];
            $wChar = $this->GetStringWidth($char);
            
            if ($x + $wChar >= $maxWidth) {
                $x = $this->PDFPage->left;
                $y += self::PointsToMM($fontSize);
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
         * Печать изображений начинается с самого первого листа
         */
        $this->setPage(0);
        $areas = array(
            $this->PDFPage->Index() => array(
            'page' => NULL,
            'area' => array(
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0
            )
        ));
        $areas[$this->PDFPage->Index()]['page'] = $this->PDFPage;
        $areas[$this->PDFPage->Index()]['area']['left'] = $this->PDFPage->left;
        $areas[$this->PDFPage->Index()]['area']['top'] = $this->PDFPage->top;
        /*Сначала получим главное изображение*/
        $mainImg = (new linq($images))->first(function($img){
            return $img['IsMain'] === 1;
        });
        !$mainImg && ($mainImg = $images[0]);
        
        /*Определим параметры для печати главного изображения*/
        $printW = $this->PDFPage->getCenter('x') - $this->PDFPage->workAreaLeft - 2;
        $printH = floor($mainImg['Height'] * ($printW / $mainImg['Width']));
        $maxX = $this->PDFPage->getCenter('x') - 2;
        $areas[$this->PDFPage->Index()]['area']['right'] = $maxX;
        
        $this->Image(
            $mainImg['FileName'], 
            $this->PDFPage->left, 
            $this->PDFPage->top, 
            $printW, 
            $printH
        );
        
        $y = $this->PDFPage->top + $printH + 2 * self::$thumbPaddingV;
        $x = $this->PDFPage->left;
        $areas[$this->PDFPage->Index()]['area']['bottom'] = $y;
        
        $thumbMaxH = 0;
        
        $thumbW = floor($printW * self::$thumbImgWidth);
        
        for ($i = 0; $i < count($images); ++$i) {
            $img = $images[$i];
//            if ($img['IdImage'] === $mainImg['IdImage']) {
//                continue;
//            }
            
            $thumbH = floor($img['Height'] * ($thumbW / $img['Width']));
            if ($thumbH > $thumbMaxH) {
                $thumbMaxH = $thumbH;
                /*Запоминаем на данном листе нижнюю границу области, занятой картинками*/
                $areas[$this->PDFPage->Index()]['area']['bottom'] = $y + $thumbMaxH;
            }
            $thumbMaxH = $thumbH > $thumbMaxH ? $thumbH : $thumbMaxH;
            
            if ($x + $thumbW + self::$thumbPaddingH > $maxX) {
                /*В данной строке изображение уже не поместится - переходим на новую строку*/
                $y += $thumbMaxH + self::$thumbPaddingV;
                /*А абсциссу ставим на начало строки*/
                $x = $this->PDFPage->workAreaLeft;
                /*Т.к. начинается новая строка, то в ней еще не известна максимальная высота изображения*/
                $thumbMaxH = 0;
            }
            if ($y + $thumbH > $this->PDFPage->workAreaBottom) {
                /*Данная строка уже не помещается на листе - переходим на новый лист*/
                $nextIndex = $this->PDFPage->Index() + 1;
                if (array_key_exists($nextIndex, $this->PDFPages)) {
                    $this->setPage($nextIndex);
                }
                else {
                    $this->AddPage(self::$orientation, self::$pageSize, 0, false);
                }
                if (!array_key_exists($this->PDFPage->Index(), $areas)) {
                    $areas[$this->PDFPage->Index()] = array(
                        'page' => NULL,
                        'area' => array(
                            'top' => 0,
                            'right' => 0,
                            'bottom' => 0,
                            'left' => 0
                        )
                    );
                    $areas[$this->PDFPage->Index()]['page'] = $this->PDFPage;
                    $areas[$this->PDFPage->Index()]['area']['left'] = $this->PDFPage->left;
                    $areas[$this->PDFPage->Index()]['area']['top'] = $this->PDFPage->top;
                    $areas[$this->PDFPage->Index()]['area']['right'] = $maxX;
                    /*На новой странице bottom = top, пока не будут добавлены данные*/
                    $areas[$this->PDFPage->Index()]['area']['bottom'] = $this->PDFPage->top;
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
        
        return $areas;
    }
    
    /*Функция получает соотношение сторон изображения для сохранения пропорций при печати*/
    private static function getImgRatio($img) {
        $ratio = 1;
        if ($img['Width'] !== 0 && $img['Height'] !== 0) {
            $ratio = $img['Width'] / $img['Height'];
        }
        return $ratio;
    }
    
    function printParams($params, $pageIndex = null) {
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
        
        $pageSide = 'l';
        
        $this->setPage($pageIndex !== null ? $pageIndex : 0);
        $pdf = $this;
        $fontSize = self::$standFontSize * .5;
        
        $this->SetFont(self::$fontName, '', $fontSize);
        $leftColL = $maxWidth = $leftColR = $rightColL = $rightColR = 0;
        $getCoords = function($pageSide) use (&$leftColL, &$leftColR, &$rightColL, &$rightColR, &$maxWidth, $pdf){
            if ( $pageSide === 'l') {

                /*Определим  начальную абсциссу левой колонки*/
                $leftColL = $pdf->PDFPage->workAreaLeft;
                /*Определим максимальную ширину области печати параметров лота*/
                $maxWidth = $pdf->PDFPage->getCenter('x') - 2;
                /*Определим  конечную абсциссу левой колонки*/
                $leftColR = $leftColL + ceil(($maxWidth - $leftColL) * .3);
                /*Определим  начальную абсциссу правой колонки*/
                $rightColL = $leftColR + 10;
                /*Определим  конечную абсциссу левой колонки*/
                $rightColR = $pdf->PDFPage->getCenter('x') - 2;
            }
            else {

                /*Определим  начальную абсциссу левой колонки*/
                $leftColL = $pdf->PDFPage->getCenter('x') + 2;
                /*Определим максимальную ширину области печати параметров лота*/
                $maxWidth = $pdf->PDFPage->workAreaRight;
                /*Определим  конечную абсциссу левой колонки*/
                $leftColR = $leftColL + ceil(($maxWidth - $leftColL) * .3);
                /*Определим  начальную абсциссу правой колонки*/
                $rightColL = $leftColR + 10;
                /*Определим  конечную абсциссу левой колонки*/
                $rightColR = $pdf->PDFPage->workAreaRight;
            }
        };
        $getCoords($pageSide);
        
        /*Определим интервал для строки*/
        $lineInterval = self::PointsToMM($fontSize) + 2;
        /*Определим максимальную нижнюю границу для размещения данных*/
        $maxBottom = $this->PDFPage->workAreaBottom - $lineInterval;
        
        $printChar = function($char, &$x, &$y, $maxWidth, $defX) use ($pdf, $lineInterval){
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
                if ($pageSide === 'r') {
                    $pageSide = 'l';
                    /*Переходим на новый лист*/
                    $nextIndex = $this->PDFPage->Index() + 1;
                    if (array_key_exists($nextIndex, $this->PDFPages)) {
                        $this->setPage($nextIndex);
                    }
                    else {
                        $this->AddPage(self::$orientation, self::$pageSize, 0, false);
                    }
                }
                else {
                    $pageSide = 'r';
                }
                $yVal = $this->PDFPage->workAreaTop;
                $yCap = $this->PDFPage->workAreaTop;
                $getCoords($pageSide);
            }
        }
        
        return true;
    }
    
    public function FinishedDocument() {
        if ($this->numeratePages) {
            $count = count($this->PDFPages);
            for ($i = 0; $i < $count; ++$i) {
                $this->setPage($i);
                $this->SetFont(self::$fontName, '', self::$headerFontSize);
                $x = $this->PDFPage->workAreaRight - self::$padding - 15;
                $y = self::$padding - self::PointsToMM(self::$headerFontSize);
                $this->Text($x, $y, 'Page ' . ($i + 1) . ' of ' . $count);
            }
        }
    }


    public static function PointsToMM($points) {
        /*1 point = 1/72 "*/
        return ceil($points / 72 * 25.4);
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
        $this->state = 2;
    }
    
    public function pageExists($index) {
        return $index >= 0 && array_key_exists($index, $this->PDFPages);
    }
    
    public static function getStandartLotPDFFileName($lot) {
        $saleDate = '';
        $manufactYear = '';
        $maker = '';
        $model = '';
        $lotKey = '';
        $getSD = function($dt){
            $res = '';
            if ($dt) {
                if (gettype($dt) == gettype('aaa')) {
                    $dt = new DateTime($dt);
                }
                $res = $dt->format('Y-m-d') . ' ';
            }
            return $res;
        };
        if (is_array($lot)) {
            $saleDate = $getSD($lot['SaleDate']);
            $manufactYear = $lot['ManufactYear'] . ' ';
            $maker = base64_decode($lot['Maker']) . ' ';
            $model = base64_decode($lot['Model']) . ' ';
            $lotKey = $lot['Key'] . ' ';
        }
        else if (is_object($lot)) {
            $saleDate = $getSD($lot->SaleDate);
            $manufactYear = $lot->ManufactYear . ' ';
            $maker = base64_decode($lot->Maker) . ' ';
            $model = base64_decode($lot->Model) . ' ';
            $lotKey = $lot->Key . ' ';
        }
        $fName = preg_replace('/\s+/', ' ', trim($saleDate . $manufactYear . $maker . $model .$lotKey));
        $fName = preg_replace('/[^\w\d\s,._()-]/', ' ', $fName);
        return $fName . '.pdf';
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
    public function getCenter($coord) {
        if (strtolower($coord) === 'y') {
            return floor($this->workAreaBottom - ($this->workAreaBottom - $this->workAreaTop) / 2);
        }
        else {
            return floor($this->workAreaRight - ($this->workAreaRight - $this->workAreaLeft) / 2);
        }
    }
}