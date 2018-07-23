<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию из БД о лоте*/

$key = 'Key';
$keyLot = base64_decode($_REQUEST[$key]);

$key = '_dataURL';
$dataURL = base64_decode($_REQUEST[$key]);

$key = 'DataFolder';
$pictStorage = base64_decode($_REQUEST[$key]);

$key = 'forceSync';
$forceSync = array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : false;

/*Получим идентификатор аукциона, чтобы понять, каким именно способом нужно загружать данные*/
$key = 'IdAuction';
$IdAuction = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';

$auction = $db->auctiones->getEntity($IdAuction);

switch(strtolower($auction['Name'])) {
    case 'iaai':
        $pictStorage = FileSystem::getPictStoragePath($auction, $keyLot);
        break;
}
/*
 * Если для лота $pictStorage по каким-либо причинам не определен,
 * зададим его принудительно
 */
!$pictStorage && ($pictStorage = FileSystem::getPictStoragePath($auction, $keyLot));
$report = array();

if ($auction !== null) {
    $IsNewLot = false;
    $pdfFile = null;
    function targetDirectory($pictStorage){
        return '.' . DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $pictStorage;
    }
    /*Такой аукцион есть в списке и у него есть обработчик*/
    $lot = $db->lot_list->getFirstRow('`IdAuction`=' . $auction['IdAuction'] 
        . ' AND `Key`=\'' . preg_replace(StandPrototype::$regexp['safeSQLValue'], '', $keyLot) . '\''
    );
    if ($lot === null) {
        /*
         * Лот еще не создан. Создадим его, чтобы связать с ним параметры и изображения.
         * Флаг $forceSync в этом случае не имеет значения, т.к. с лотом еще ничего не связано
         */
        $lot = $db->lot_list->getEmptyEntity();
        $lot['Key'] = $keyLot;
        $lot['IdAuction'] = $IdAuction;
        $lot['DataFolder'] = base64_encode($pictStorage);
        $lot['BaseURL'] = base64_encode($dataURL);
        $lot = $db->lot_list->Insert($lot);
        $IsNewLot = true;
    }
    else if ($forceSync) {
        /*
         * При такой синхронизации нужно удалить все значения параметров и изображения, 
         * связанные с этим лотом. Прочую информацию, связанную с лотом, нужно сохранить.
         */
        $oLot = new lot_list($lot);
        $deletedTypes = array('lot_params_values' => 'lot_params_values', 'lot_images' => 'lot_images');
        (new linq($oLot->getExternalLinks()))->for_each(function($extLink) use ($deletedTypes, $db){
            $type = strtolower($extLink->ObjType());
            if (!array_key_exists($type, $deletedTypes)) {
                return;
            }
            $extLink->Delete($db);
        });
    }
    
    /*
     * Флаг, который покажет, найден ли данный лот
     */
    $isFound = NULL;
    $report['auction'] = $auction['Name'];
    try {
        require_once strtolower($auction['Name']) . '_data.php';
    }
    catch (Exception $e) {
        echo '{"syncStatus":false,"message":"Критическая ошибка синхронизации лота ' . $lot['Key'] . '","key":"' . base64_encode($lot['Key']) . '"}';
        exit();
    }
    
    if ($IsNewLot && !$isFound) {
        /*Если не удалось получить информацию по лоту и он остался "пустышкой", тогда удаляем его*/
        $db->lot_list->Delete($lot);
    }
    /*Обновим дату последней синхронизации аукциона*/
    $auction['DateLastSync'] = date('Y-m-d H:i:s');
    $db->auctiones->Update($auction);
    
    /*Сохраним PDF*/
    
    $pdfFile = targetDirectory($pictStorage) . DIRECTORY_SEPARATOR . PDF::getStandartLotPDFFileName($db->lot_list->getEntity($lot['IdLot']));
    
    if ($IsNewLot || $forceSync) {
        $lot = new lot_list($db->lot_list->getEntity($lot['IdLot']));
        $file = $pdfFile;
        $images = $lot->getImages(true);
        (new linq($images))->for_each(function($img, $k) use (&$images) {
            $images[$k]['FileName'] = realpath('.' . 
                DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . 
                DIRECTORY_SEPARATOR .  base64_decode($img['FileName'])
            );
        });

        $params = $lot->getParams(true);
        /*Подключаем генератор PDF*/
        require_once './php/pdf.php';
        /*Генерируем PDF*/
        require_once strtolower($auction['Name']) . '_lot_print_data.php';
        
    }
}






?>