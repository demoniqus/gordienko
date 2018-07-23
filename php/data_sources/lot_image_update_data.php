

<?php
$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);
/*Получим значение для обновления*/
$entity = (new linq(array('IdImage', 'Visible', 'IsMain')))->toAssoc(
    function(&$v, $key){
        return $v;
    },
    function(&$v, $key){
        return array_key_exists($v, $_REQUEST) ? $_REQUEST[$v] : null;
    }
)->getData();

if ($entity['IsMain']) {
    /*Главным может быть только одно изображение, поэтому скидываем флаг у всех остальных*/
    $_entity = $db->lot_images->getEntity($entity['IdImage']);
    if ($_entity) {
        /*Перед запросом на обновление отключаем безопасный режим*/
        $query = 'set sql_safe_updates = 0;UPDATE lot_images SET `IsMain`=0 WHERE `IdLot`=' . $_entity['IdLot'];
        $db->query($query);
    }
}

$db->lot_images->Update($entity);

echo json_encode($db->lot_images->getEntity($entity['IdImage']));



?>