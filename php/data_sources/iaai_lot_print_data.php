<?php

/*Имеется лот, аукцион, наименование временного файла, массив изображений и параметров лота*/

//
//$pdf = new PDF(PDF::$orientation);
//
//$pdf->SetDisplayMode('fullpage', 'single');
//
//$lotHeader = ($lot->ManufactYear) . '    ' . base64_decode($lot->Maker) . ' ' . base64_decode($lot->Model);
//$pdf->printLotHeader($lotHeader);
//$pdf->printParams($params);
//$pdf->printImages($images);
//$pdf->FinishedDocument();
//
//
//$pdf->Output('F', $file);




$pdf = new PDF();

$pdf->SetDisplayMode('fullpage', 'single');

$lotHeader = ($lot->ManufactYear) . '    ' . base64_decode($lot->Maker) . ' ' . base64_decode($lot->Model);

$lineColor = array('r' => 120, 'g' => 120, 'b' => 120);

/*Печатаем заголовок*/
$pdf->SetTextColor(201, 1, 7);
$text = iconv('utf-8', 'cp1251', $lotHeader);
$fontSize = PDF::$standFontSize * 1.5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
$maxWidth = $pdf->getPDFPage()->workAreaRight;
$x = $pdf->getPDFPage()->left;
$y = $pdf->getPDFPage()->top + PDF::PointsToMM($fontSize) + 2;

$printText = function ($text, &$x, &$y, $fontSize, $maxWidth, $startLeft) use ($pdf) {
    for($i = 0; $i < strlen($text); ++$i) {
        $char = $text[$i];
        $wChar = $pdf->GetStringWidth($char);

        if ($x + $wChar >= $maxWidth) {
            $x = $startLeft;
            $y += PDF::PointsToMM($fontSize);
        }
        $pdf->Text($x, $y, $char);
        $x += $wChar;
    }
};

$printText($text, $x, $y, $fontSize, $maxWidth, $pdf->getPDFPage()->left);

/*После печати заголовка запомним новые координаты для ординаты*/
$pdf->getPDFPage()->top = $y;

$pdf->SetTextColor(0, 0, 0);

/*Печатаем подзаголовок*/
$linqParams = new linq($params);
$text = array();
$slot = $linqParams->first(function($p){
    return strtolower($p['Name']) === 'slot';
});
if ($slot) {
    $text[] = 
    $slot = 'A - #' . $slot['Value'];
}
$stockNo = $linqParams->first(function($p){
    return strtolower($p['Name']) === 'stockno';
});
if ($stockNo) {
    $text[] = 'Stock#: ' . $stockNo['Value'];
}
if ($lot->VIN) {
    
    $text[] = 'VIN: ' . $lot->VIN;
}

$fontSize = PDF::$standFontSize * .8;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
$x = $pdf->getPDFPage()->left;
$y = $pdf->getPDFPage()->top + PDF::PointsToMM($fontSize) + 2;
for ($i = 0; $i < count($text); $i++) {
    
    $printText($text[$i], $x, $y, $fontSize, $maxWidth, $pdf->getPDFPage()->left);
    
    if ($i < count($text) - 1) {
        $pdf->SetTextColor($lineColor['r'], $lineColor['g'], $lineColor['b']);
        $printText('  |  ', $x, $y, $fontSize, $maxWidth, $pdf->getPDFPage()->left);
        $pdf->SetTextColor(0, 0, 0);
    }
    
}
/*После печати заголовка запомним новые координаты для ординаты*/
$pdf->getPDFPage()->top = $y + 6;

/*Для печати параметров нужно запомнить ординату*/
$cellTop = $pdf->getPDFPage()->top;

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

$padding = 5;
$x = $leftColL + $padding;
$startLeft = $x;
$y = $cellTop + $padding;
//debug($linqParams->toAssoc(function($p){return $p['Name'];}, function($p){ return $p['Value'];})->getData());
//exit();
$text = $linqParams->first(function($p){
    return strtolower($p['Name']) === 'branchlink';
});
if ($text) {
    $pdf->SetTextColor(29, 103, 193);
    $fontSize = PDF::$standFontSize;
    $pdf->SetFont(PDF::getFontName(), '', $fontSize);
    $printText($text['Value'], $x, $y, $fontSize, $maxWidth - 2 * $padding, $startLeft);
}

$x = $leftColL + $padding;
$y += $lineInterval;
$date = array();
(new linq(array('day', 'currentmonth', 'date', 'auctionhours', 'auctionminutes', 'meridian', 'usertimezoneabb')))->for_each(function($pName) use (&$date, $linqParams) {
    $pValue = $linqParams->first(function($p) use ($pName){ return strtolower($p['Name']) === $pName;});
    if ($pValue && $pValue['Value']) {
        switch ($pName) {
            case 'auctionhours':
                $date[] = ', ' . $pValue['Value'] . ':';
                break;
            case 'auctionminutes':
                $date[] = $pValue['Value'];
                break;
            case 'usertimezoneabb':
                $date[] = ' (' . $pValue['Value'] . ')';
                break;
            default:
                $date[] = $pValue['Value'] . ' ';
        }
    }
});
$fontSize = PDF::$standFontSize * .8;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
if ($date && count($date) > 0) {
    $pdf->SetTextColor(29, 103, 193);
    $printText(implode('', $date), $x, $y, $fontSize, $maxWidth - 2 * $padding, $startLeft);
    $pdf->SetTextColor($lineColor['r'], $lineColor['g'], $lineColor['b']);
    $printText('  |  ', $x, $y, $fontSize, $maxWidth, $startLeft);
    $pdf->SetTextColor(0, 0, 0);
}

$seller = $linqParams->first(function($p){
    return strtolower($p['Name']) === 'seller';
});
if ($seller && $seller['Value']) {
    $printText($seller['Value'], $x, $y, $fontSize, $maxWidth - 2 * $padding, $leftColL + $padding);
}

$x = $leftColL + $padding;
$y += $lineInterval;

(new linq(array('saledoc', 'brand', 'notes', 'acv', 'estimatedrepaircost')))->for_each(function($pName) 
        use ($linqParams, &$x, &$y, $printText, $maxWidth, $padding, $pdf, $fontSize, $startLeft, $lineColor) {
    $pValue = $linqParams->first(function($p) use ($pName){ return strtolower($p['Name']) === $pName;});
    if ($pValue && $pValue['Value']) {
        $prefix = '';
        switch ($pName) {
            case 'acv':
            case 'estimatedrepaircost':
                $prefix = strtoupper($pName) . ': ';
                break;
        }
        $printText($prefix . $pValue['Value'], $x, $y, $fontSize, $maxWidth - 2 * $padding, $startLeft);
        $pdf->SetTextColor($lineColor['r'], $lineColor['g'], $lineColor['b']);
        $printText('  |  ', $x, $y, $fontSize, $maxWidth, $startLeft);
        $pdf->SetTextColor(0, 0, 0);
    }
    
});
$y += 3;
$pdf->SetDrawColor($lineColor['r'], $lineColor['g'], $lineColor['b']);
$pdf->Line($startLeft, $y, $rightColR - $padding, $y);
$y += 3;

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

        $yVal = ($yCap > $yVal ? $yCap : $yVal) + $fontSizeMM * .5;
        $yCap = $yVal;
        $pdf->SetXY($leftColL - 2, $cellTopBorderY);
        $pdf->Cell($cellWidth + 4, $yCap - $cellTopBorderY - $fontSizeMM * .5 + 2, '', 0, 2, 'L');

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
$shortListParams = array(
    array(
        'Caption' => 'Selling Branch:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'branchlink';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Auction Date and Time:',
        'Value' => $date ? implode('', $date) : ''
    ),
    array(
        'Caption' => 'Lane/Item #:',
        'Value' => $slot ? $slot : ''
    ),
    array(
        'Caption' => 'Seller:',
        'Value' => $seller ? $seller['Value'] : ''
    ),
    array(
        'Caption' => 'Actual Cash Value:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'acv';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Estimated Repair Cost:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'estimatedrepaircost';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Title/Sale Doc:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'saledoc';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Title/Sale Doc Brand:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'brand';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Title/Sale Doc Notes:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'notes';})) &&
            $p ? $p['Value'] : '')
    )
);
            
$fontSize = PDF::$standFontSize * .5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
/*Смещаем координаты*/
$yVal = $yCap = $y;
///*Размер шрифта из пунктов переводим в мм*/
$fontSizeMM = PDF::PointsToMM($fontSize);
/*Печатаем параметры*/
$printParams($shortListParams, $fontSizeMM, $yCap, $yVal);

/*Рисуем общую рамку*/
$pdf->SetXY($leftColL - 2, $cellTop);
$pdf->Cell($cellWidth + 4, $yCap - $cellTop - $fontSizeMM + 2, '', 1, 2, 'L');


$y = $cellTop + $yCap - $cellTop - $fontSizeMM + 2 + 10;


/*Блок информации о VIN*/
$fontSize = PDF::$standFontSize * 1.5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
$x = $leftColL;
$printText('VIN Details', $x, $y, $fontSize, $maxWidth, $pdf->getPDFPage()->left);
$y += 3;
$pdf->Line($leftColL, $y, $rightColR, $y);
$y += 3;

$shortListParams = array(
    array(
        'Caption' => 'VIN:',
        'Value' => $lot->VIN
    ),
    array(
        'Caption' => 'Vehicle:',
        'Value' => 'automobile'
    ),
    array(
        'Caption' => 'Model:',
        'Value' => base64_decode($lot->Model)
    ),
    array(
        'Caption' => 'Engine:',
        'Value' => (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'engine';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Fuel Type:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'fuel type';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Cylinders:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'cylinders';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Transmission:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'transmission';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Exterior/Interior:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'exterior/interior';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Entertainment:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'entertainment';})) &&
            $p ? $p['Value'] : '')
    )
);
            
$fontSize = PDF::$standFontSize * .5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
/*Смещаем координаты*/
$yVal = $yCap = $y;
///*Размер шрифта из пунктов переводим в мм*/
$fontSizeMM = PDF::PointsToMM($fontSize);
/*Печатаем параметры*/
$printParams($shortListParams, $fontSizeMM, $yCap, $yVal);

/*Конец блока о VIN*/

$y = $pdf->getPDFPage()->top + $lineInterval;

/*Блок информации о CONDITIONS*/

$fontSize = PDF::$standFontSize * 1.5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
$x = $leftColL;
$printText('IAA Condition Details', $x, $y, $fontSize, $maxWidth, $pdf->getPDFPage()->left);
$y += 3;
$pdf->Line($leftColL, $y, $rightColR, $y);
$y += 3;

$shortListParams = array(
    array(
        'Caption' => 'Loss:',
        'Value' => (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'loss';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Primary Damage:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'primary damage';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'secondary damage:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'secondary damage';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Odometer:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'odometer';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Start Code:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'start code';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Key:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'key';})) &&
            $p ? $p['Value'] : '')
    ),
    array(
        'Caption' => 'Vehicle Wheels:',
        'Value' => 
            (($p = $linqParams->first(function($param){ return strtolower($param['Name']) === 'vehicle wheels';})) &&
            $p ? $p['Value'] : '')
    )
);
            
$fontSize = PDF::$standFontSize * .5;
$pdf->SetFont(PDF::getFontName(), '', $fontSize);
/*Смещаем координаты*/
$yVal = $yCap = $y;
///*Размер шрифта из пунктов переводим в мм*/
$fontSizeMM = PDF::PointsToMM($fontSize);
/*Печатаем параметры*/
$printParams($shortListParams, $fontSizeMM, $yCap, $yVal);

/*Конец блока о CONDITIONS*/




$pdf->AddPage(PDF::$orientation, PDF::getPageSize());
$pdf->printParams($params, $pdf->getPDFPage()->Index() - 1);
$pdf->FinishedDocument();


$pdf->Output('F', $file);

?>