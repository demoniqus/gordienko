<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию о лоте*/
$key = 'IdLot';
$lot = null;
if (array_key_exists($key, $_REQUEST)) {
    $IdLot = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';
    $lot = $db->lot_list->getEntity($IdLot);
    
    (new linq($lot))
        ->for_each(function(&$v, $k) use (&$lot){ 
            array_key_exists($k, array('VIN' => true, 'Key' => true)) && gettype($v) === gettype('') && ($lot[$k] = base64_encode($v));
        });
    $lot['_images'] = (new linq($db->lot_images->getRows('`Visible`=1 AND `IdLot`=' . $lot['IdLot'])))
            ->select(function($img){
                unset($img['OrigName']);
                $img['FileName'] = base64_encode(GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . base64_decode($img['FileName']));
                return $img;
            })
            ->getData();
    $lot['_params'] = (new linq($db->query('select ap.*, lpv.IdParamValue, lpv.Value from (select * from lot_params_values where `IdLot`=' . 
            $lot['IdLot'] . ') as lpv left join (select * from auction_params where `IdAuction`=' . 
            $lot['IdAuction'] . ' AND `Visible`=1) as ap on lpv.IdParam = ap.IdParam WHERE ap.IdParam IS NOT NULL')))
            ->where(function($row){ return $row !== null && count($row) > 0; })
            ->for_each (function(&$row){
                $k = 'Name';
                $row[$k] = $row[$k] === null ? null : base64_encode($row[$k]);
                $k = 'Caption';
                $row[$k] = $row[$k] === null ? null : base64_encode($row[$k]);

            });
}
echo json_encode($lot);

?>