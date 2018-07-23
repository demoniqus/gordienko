

<?php
/*Получим список аукционов, доступных для работы*/
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
}

echo '{"records":';

echo json_encode(
    (new linq($db->auction_params->getRows('`IdAuction`=' . $IdAuction)))
    ->for_each(function(&$el){
        foreach ($el as &$v) {
            gettype($v) === gettype('') && ($v = base64_encode($v));
        }
    })
    ->getData()
);

echo '}';

?>