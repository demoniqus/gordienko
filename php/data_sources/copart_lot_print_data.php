<?php

/*Имеется лот, аукцион, наименование временного файла, массив изображений и параметров лота*/

$pdf = new PDF();

$pdf->SetDisplayMode('fullpage', 'single');

$lotHeader = ($lot->ManufactYear) . '    ' . base64_decode($lot->Maker) . ' ' . base64_decode($lot->Model);

$pdf->printLotHeader($lotHeader);
$imgAreas = $pdf->printImages($images);


/*
 * Печать параметров начинается с самого первого листа
 */

$pdf->setPage(0);

/*Определим  начальную абсциссу левой колонки*/

$leftColL = $pdf->getPDFPage()->getCenter('x') + 2;
/*Определим максимальную ширину области печати параметров лота*/
$maxWidth = $pdf->getPDFPage()->workAreaRight;
/*Определим  конечную абсциссу левой колонки*/
$leftColR = $leftColL + ceil(($maxWidth - $leftColL) * .3);
/*Определим  начальную абсциссу правой колонки*/
$rightColL = $leftColR + 10;
/*Определим  конечную абсциссу левой колонки*/
$rightColR = $pdf->getPDFPage()->workAreaRight;
/*Определим интервал для строки*/
$lineInterval = PDF::PointsToMM(PDF::$standFontSize + 3);

$cellWidth = $rightColR - $leftColL;

/*Определим максимальную нижнюю границу для размещения данных*/
$maxBottom = $pdf->getPDFPage()->workAreaBottom - $lineInterval;

$printBlockHeader = function($text, $cellHeight) use ($pdf, $cellWidth, $leftColL){
    $pdf->SetXY($leftColL - 2, $pdf->getPDFPage()->top);
    $pdf->SetFillColor(13, 93, 184);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($cellWidth + 4, $cellHeight, $text, 0, 2, 'L', true);
};

$printChar = function($char, &$x, &$y, $maxWidth, $defX) use ($pdf, $lineInterval){
    $wChar = $pdf->GetStringWidth($char);

    if ($x + $wChar >= $maxWidth) {
        $x = $defX;
        $y += $lineInterval;
    }
    $pdf->Text($x, $y, $char);
    $x += $wChar;

};

$printParams = function($params, $fontSizeMM, &$yCap, &$yVal) use ($leftColL, $leftColR, $rightColL, $rightColR, $pdf, $printChar, $maxBottom, $cellWidth){

    foreach ($params as $k => $v) {
        $caption = $v['Caption'] ? $v['Caption'] : $v['Name'];
        $caption = iconv('utf-8', 'cp1251', $caption);
        $cellTopBorderY = $yCap - $fontSizeMM;
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

        $yVal = ($yCap > $yVal ? $yCap : $yVal) + $fontSizeMM;
        $yCap = $yVal;
        $pdf->SetXY($leftColL - 2, $cellTopBorderY);
        $pdf->Cell($cellWidth + 4, $yCap - $cellTopBorderY - $fontSizeMM + 2, '', 1, 2, 'L');

        if ($yCap >= $maxBottom) {
            /*Переходим на новый лист*/
            $nextIndex = $pdf->getPDFPage()->Index() + 1;
            if (array_key_exists($nextIndex, $pdf->getPDFPages())) {
                $pdf->setPage($nextIndex);
            }
            else {
                $pdf->AddPage(PDF::$orientation, PDF::getPageSize(), 0, false);
            }
            $yVal = $pdf->getPDFPage()->workAreaTop;
            $yCap = $pdf->getPDFPage()->workAreaTop;
        }
        else {
            $yCap += 2;
            $yVal += 2;
            $pdf->getPDFPage()->top = $yCap;
        }
    }
};



/*Выбираем параметры для печати блока параметров*/
$linqParams = new linq($params);
$shortListParams = array(
    array(
        'Caption' => 'Doc Type:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'ts';})) &&
            $p ? $p['Value'] : '') . 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'td';})) &&
            $p ? ' - ' . $p['Value'] : '')
    ),
    array(
        'Caption' => 'Odometer:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'orr';})) &&
            $p ? $p['Value'] : '') . 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'ord';})) &&
            $p ? ' (' . $p['Value'] . ')' : '')
    ),
    array(
        'Caption' => 'Highlights:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'lcd';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Primary Damage:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'dd';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Secondary Damage:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'sdd';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Est.Retail Value:',
        'Value' => (function()use ($linqParams){
            $val = '';
            $cost = (($p = $linqParams->first(function($param) { return strtolower($param['Name']) === 'rc';})) &&
            $p ? $p['Value'] : '0');
            if ($cost * 1 !== 0) {
                $currency = (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'cuc';})) &&
                $p ? $p['Value'] : '');
                $currency = strtoupper($currency);
                switch ($currency) {
                    case 'USD':
                        $cost = '$' . $cost;
                        break;
                }
                $val = $cost . ' ' . $currency;
            }
            
            return $val;
        })()
    ),
    array(
        'Caption' => 'VIN:',
        'Value' => $lot->VIN
    ),
    array(
        'Caption' => 'Body Style:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'bstl';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Color:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'clr';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Engine Type:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'egn' || strtolower($param['Name']) === 'eng' ;})) && // обозначение может в любой момент поменяться
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Drive:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'drv';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Cylinders:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'cy';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Fuel:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'ft';})) && //А может ftd?
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Keys:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'hk';})) && 
            $p ? $p['Value'] : '')
    )
);
/*Напечатаем закрашенную ячейку*/
/*Для этого ставим более крупный шрифт*/
$fontSize = PDF::$standFontSize + 3;

$pdf->SetFont(PDF::getFontName(), '', $fontSize);
/*Определяем высоту области, которую закрасим цветом*/
$cellHeight = PDF::PointsToMM($fontSize);
/*Печатаем заголовок блока*/
$printBlockHeader('Lot # ' . $lot->Key, $cellHeight);

/*Переопределяем для печати параметры цвета шрифта и границы для печати параметров блока*/
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(220, 220, 220);

$fontSize = PDF::$standFontSize * .5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
/*Смещаем координаты*/
$yVal = $pdf->getPDFPage()->top + $cellHeight + $lineInterval - 3;
$yCap = $pdf->getPDFPage()->top + $cellHeight + $lineInterval - 3;
/*Размер шрифта из пунктов переводим в мм*/
$fontSizeMM = PDF::PointsToMM($fontSize);
/*Печатаем параметры*/
$printParams($shortListParams, $fontSizeMM, $yCap, $yVal);
/*Выполняем смещение для размещения нового блока*/
$pdf->getPDFPage()->top = $yCap + 10;

/*Печатаем следующий блок*/

/*Для заголовка блока ставим более крупный шрифт*/
$fontSize = PDF::$standFontSize + 3;

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont(PDF::getFontName(), '', $fontSize);

$cellHeight = PDF::PointsToMM($fontSize);
$printBlockHeader('Sale Information', $cellHeight);
/*Определяем параметры для печати блока*/
$shortListParams = array(
    array(
        'Caption' => 'Location:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'yn';})) &&
            $p ? $p['Value'] : '') . 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'td';})) &&
            $p ? ' - ' . $p['Value'] : '')
    ),
    array(
        'Caption' => 'Sale Date:',
        'Value' => $lot->getSaleDate('d.m.Y')
    )
);
            
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(220, 220, 220);

$fontSize = PDF::$standFontSize * .5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
/*Смещаем координаты*/
$yVal = $pdf->getPDFPage()->top + $cellHeight + $lineInterval - 3;
$yCap = $pdf->getPDFPage()->top + $cellHeight + $lineInterval - 3;
/*Размер шрифта из пунктов переводим в мм*/
$fontSizeMM = PDF::PointsToMM($fontSize);
/*Печатаем параметры*/
$printParams($shortListParams, $fontSizeMM, $yCap, $yVal);

$pdf->AddPage(PDF::$orientation, PDF::getPageSize());
$pdf->printParams($params, $pdf->getPDFPage()->Index() - 1);
$pdf->FinishedDocument();


$pdf->Output('F', $file);

?>