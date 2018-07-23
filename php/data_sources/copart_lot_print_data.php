<?php

/*Имеется лот, аукцион, наименование временного файла, массив изображений и параметров лота*/


$pdf = new PDF(PDF::$orientation);

$pdf->SetDisplayMode('fullpage', 'single');


$lotHeader = ($lot['ManufactYear']) . '    ' . base64_decode($lot['Maker']) . ' ' . base64_decode($lot['Model']);
$pdf->printLotHeader($lotHeader);
$pdf->printParams($params);
$pdf->printImages($images);


$pdf->Output('F', $file);

?>