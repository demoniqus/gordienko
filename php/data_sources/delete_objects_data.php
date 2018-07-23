<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

$entityType = $_POST['ObjectType'];
$ObjectsIdList = $_POST['ObjectsIdList'];
$errors = array();
try {
    (new linq($ObjectsIdList))->for_each(function($Id) use ($entityType, $db, &$errors){
        if ($db->$entityType->getObject($Id)->Delete()) {
            
        }
        else {
            $errors[] = $Id;
            
        }
    });
}
catch (Exception $ex) {
    
}
if (count($errors) > 0) {
    echo '{"error": "Не удалось удалить объекты с идентификаторами ' . join(', ', $errors) . '"}';
    
}
else {
    echo '{"success": true}';
}


?>