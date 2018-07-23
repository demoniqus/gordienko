<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию о лоте*/
$key = 'IdParam';
$auction_param = null;
if (array_key_exists($key, $_REQUEST)) {
    $IdParam = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';
    $auction_param = $db->auction_params->getEntity($IdParam);
    (new linq($auction_param))
        ->for_each(function(&$v, $k) use (&$auction_param){ 
            gettype($v) === gettype('') && ($auction_param[$k] = base64_encode($v));
        });
}
echo json_encode($auction_param);

?>