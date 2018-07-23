

<?php
/*Получим список аукционов, доступных для работы*/
$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);
/*Получим значение для обновления*/
$entity = $db->lot_list->getEmptyEntity();

(new linq($entity))->for_each(function(&$v, $key) use (&$entity){
    if (array_key_exists($key, $_REQUEST)) {
        $entity[$key] = $_REQUEST[$key];
    }
});
(new linq(array('SaleDate')))->for_each(function($keyName) use (&$entity){
    if ($entity[$keyName] && (gettype($entity[$keyName]) === gettype(''))) {
        $entity[$keyName] = base64_decode($entity[$keyName]);
    }
});
/*Поля Key и IdAuction не могут быть изменены*/
unset($entity['Key']);
unset($entity['IdAuction']);

$entity['VIN'] = base64_decode($entity['VIN']);

$db->lot_list->Update($entity);

$lot = $db->lot_list->getEntity($entity['IdLot']);
(new linq($lot))
        ->for_each(function($v, $k) use (&$lot){ 
            array_key_exists($k, array('VIN' => true, 'Key' => true)) && gettype($v) === gettype('') && ($lot[$k] = base64_encode($v));
        });

echo json_encode($lot);



?>