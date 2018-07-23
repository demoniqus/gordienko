<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию о лоте*/
$key = 'entityName';
$entity = null;
if (array_key_exists($key, $_REQUEST)) {
    $entityName = $_REQUEST[$key];
    try {
        $entity = $db->$entityName->getEmptyEntity();
    }
    catch (Exception $ex) {
        $entity = null;
    }
}
echo json_encode($entity);

?>