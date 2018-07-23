<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию из БД о лоте*/

$key = 'IdLot';
$IdLot = $_REQUEST[$key];

if ($IdLot !== null) {
    function targetDirectory($pictStorage){
        return '.' . DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $pictStorage;
    }
    
    $lot = $db->lot_list->getObject($IdLot);
    $pdfFile = targetDirectory(base64_decode($lot->DataFolder)) . DIRECTORY_SEPARATOR . PDF::getStandartLotPDFFileName($lot->getFields());
    
    if ($lot) {
        $auction = $db->auctiones->getEntity($lot->IdAuction);
        $file = $pdfFile;
        $images = $lot->getImages();
        (new linq($images))->for_each(function($img, $k) use (&$images) {
            $images[$k]['FileName'] = realpath('.' . 
                DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . 
                DIRECTORY_SEPARATOR .  base64_decode($img['FileName'])
            );
        });

        $params = $db->query(
            'select ap.Name, ap.Caption, lpv.Value from lot_params_values as lpv left ' . 
            ' join auction_params as ap on ap.IdParam=lpv.IdParam where lpv.IdLot=' .
            $lot->IdLot . ' AND ap.Visible=1 order by ap.OrderNum', 
            true
        );
        /*Подключаем генератор PDF*/
        require_once './php/pdf.php';
        /*Генерируем PDF*/
        require_once strtolower($auction['Name']) . '_lot_print_data.php';
        
    }
}
echo '{"success": true}';






?>