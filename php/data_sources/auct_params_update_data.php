<?php
/*Получим список аукционов, доступных для работы*/
$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);
/*Получим значение для обновления*/
$entity = $db->auction_params->getEmptyEntity();

(new linq($entity))->for_each(function(&$v, $key) use (&$entity){
    if (array_key_exists($key, $_REQUEST)) {
        $entity[$key] = $_REQUEST[$key];
    }
});
(new linq(array('Comment', 'Caption')))->for_each(function($keyName) use (&$entity){
    if ($entity[$keyName] && (gettype($entity[$keyName]) === gettype(''))) {
        $entity[$keyName] = base64_decode($entity[$keyName]);
    }
});
/*Поля Name и IdAuction не могут быть изменены*/
unset($entity['Name']);
unset($entity['IdAuction']);
if ($entity['IdParam'] == 0) {
    $db->auction_params->Insert($entity);
    
}
else {
    $db->auction_params->Update($entity);
    
}

echo json_encode(
    (new linq($db->auction_params->getEntity($entity['IdParam'])))
        ->for_each(function(&$v){ gettype($v) === gettype('') && ($v = base64_encode($v));})
        ->getData()
);



?>