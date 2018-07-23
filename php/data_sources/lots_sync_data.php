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

/*Получим идентификатор аукциона, чтобы понять, каким именно способом нужно загружать данные*/
$key = 'IdAuction';
$IdAuction = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';

$auction = $db->auctiones->getEntity($IdAuction);


if ($auction !== null) {
    $IsNewLot = false;
    /*Такой аукцион есть в списке и у него есть обработчик*/
    $lot = $db->lot_list->getFirstRow('`Key`=\'' . $keyLot . '\'');
    if ($lot === null) {
        /*Лот еще не создан. Создадим его, чтобы связать с ним параметры*/
        $lot = $db->lot_list->getEmptyEntity();
        $lot['Key'] = $keyLot;
        $lot['IdAuction'] = $IdAuction;
        $lot['DataFolder'] = base64_encode($pictStorage);
        $lot['BaseURL'] = base64_encode($dataURL);
        $lot = $db->lot_list->Insert($lot);
        $IsNewLot = true;
    }
    
    /*
     * Флаг, который покажет, найден ли данный лот
     */
    $isFound = NULL;
    
    require_once strtolower($auction['Name']) . '_data.php';
    
    if ($IsNewLot && !$isFound) {
        /*Если не удалось получить информацию по лоту и он остался "пустышкой", тогда удаляем его*/
        $db->lot_list->Delete($lot);
    }
    /*Обновим дату последней синхронизации аукциона*/
    $auction['DateLastSync'] = date('Y-m-d H:i:s');
    $db->auctiones->Update($auction);
}






?>