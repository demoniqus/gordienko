<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию о лоте*/
$key = 'IdImage';
$image = null;
if (array_key_exists($key, $_REQUEST)) {
    $IdImage = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';
    $image = $db->lot_images->getEntity($IdImage);
    $image['FileName'] = base64_encode(GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . base64_decode($image['FileName']));
    (new linq(array('IsMain', 'Visible')))->for_each(function($key) use (&$image){
        $image[$key] = !!$image[$key];
    });
}
echo json_encode($image);

?>