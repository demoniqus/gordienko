<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);


$key = 'IdLot';
$lot = null;
if (array_key_exists($key, $_REQUEST)) {
    $IdLot = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';
    $lot = $db->lot_list->getObject($IdLot);
}


if ($lot && $lot->IdLot > 0) {
    
    $images = $lot->getImages(true);
    (new linq($images))->for_each(function($img, $k) use (&$images) {
        $images[$k]['FileName'] = realpath('.' . 
            DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . 
            DIRECTORY_SEPARATOR .  base64_decode($img['FileName'])
        );
    });

    $params = $lot->getParams(true);
    
    /*Определим временный каталог и наименование для файла PDF*/
    $tmpCat = '.' . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($tmpCat)) {
        mkdir($tmpCat, GlobalVars::$defDirAccess);
    }
    $file = $tmpCat . DIRECTORY_SEPARATOR . PDF::getStandartLotPDFFileName($lot);
    
    /*Получим информацию об аукционе, чтобы выбрать форму для печати PDF*/
    $auction = $db->auctiones->getEntity($lot->IdAuction);

    /*Подключаем генератор PDF*/
    require_once './php/pdf.php';
    /*Генерируем файл PDF*/
    require_once './php/data_sources/' . strtolower($auction['Name']) . '_lot_print_data.php';
    
    /*Устанавливаем заголовки для вывода*/
    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    if (ob_get_level()) {
      ob_end_clean();
    }
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    // читаем файл и отправляем его пользователю
    readfile($file);
    exit;
}


    

?>