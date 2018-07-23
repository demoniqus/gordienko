

<?php
/*Получим список аукционов, доступных для работы*/
$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);




echo '{"records":';
/*Получим параметры аукционов, чтобы прикрепить их к аукционам*/

$auctiones_params = (new linq($db->auction_params->getRows()))
    ->where(function($el){ 
        $key = 'IdAuction'; 
        return $el !== null && 
            gettype($el) === gettype(array()) && 
            array_key_exists($key, $el) && 
            $el[$key] !== null;
    })
    ->toAssoc(function($el){
        return $el['IdAuction'];
    })
    ->for_each(function(&$el){
        foreach ($el as &$v) {
            gettype($v) === gettype('') && ($v = base64_encode($v));
        }
    })
    ->getData();
/*Сформируем список аукционов*/
echo json_encode(
    (new linq($db->auctiones->getRows()))
    ->where(function($el){ 
        $key = 'IdAuction'; 
        return $el !== null && 
            gettype($el) === gettype(array()) && 
            array_key_exists($key, $el) && 
            $el[$key] !== null;
    })
    ->for_each(function(&$el) use ($auctiones_params){
        foreach ($el as $k => &$v) {
            if (array_key_exists($k, array('BaseLotUrl' => true))) {
                /*Это уже кодированные в Base64 значения*/
                continue;
            }
            gettype($v) === gettype('') && ($v = base64_encode($v));
        }
        $el['auction_params'] = array_key_exists($el['IdAuction'], $auctiones_params) ? $auctiones_params[$el['IdAuction']] : null;
    })
    ->getData()
);

echo '}';



?>