<?php
/*
 * !!!!!! Данаая опция является ОПАСНОЙ.
 * При отсутствии необходимости данная опция дожна быть заблокирована
 * во избежание удаления данных или нарушения их целостности
 */
$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

$key = 'IdAuction';
$auction = null;
if (array_key_exists($key, $_REQUEST)) {
    $IdAuction = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';
    $auction = $db->auctiones->getEntity($IdAuction);
    if ($auction) {
        $db->query('delete from `lot_params_values` where `IdLot` in (select `IdLot` from `lot_list` where `IdAuction`=' . $auction['IdAuction'] . ')');
        $db->query('delete from `lot_images` where `IdLot` in (select `IdLot` from `lot_list` where `IdAuction`=' . $auction['IdAuction'] . ')');
        $db->query('delete from `auction_params` where `IdAuction`=' . $auction['IdAuction']);
        $db->query('delete from `lot_list` where `IdAuction`=' . $auction['IdAuction']);
        $path = '.' . DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $auction['Name'];
        /*Для очистки удаляем директорию с данными и заново ее создаем, уже пустую*/
        FileSystem::Remove($path, true);
        FileSystem::createDir($path);
    }
}

echo '{"success":true}';

?>