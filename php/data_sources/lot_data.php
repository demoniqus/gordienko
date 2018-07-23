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
            array_key_exists($k, array('Archive' => true)) && ($lot[$k] = !!$v);
        });
    $l = new lot_list($lot);
    $lot['_images'] = (new linq($l->getImages()))
            ->select(function($img){
                unset($img['OrigName']);
                $img['FileName'] = base64_encode(GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . base64_decode($img['FileName']));
                $k = 'Visible';
                $img[$k] = $img[$k] == '1' ? true : false;
                $k = 'IsMain';
                $img[$k] = $img[$k] == '1' ? true : false;
                return $img;
            })
            ->getData();
    $lot['_params'] = (new linq($l->getParams()))
            ->where(function($row){ return $row !== null && count($row) > 0; })
            ->for_each (function(&$row){
                (new linq(array('Name', 'Caption')))->for_each(function($k) use (&$row){
                    $row[$k] = $row[$k] === null ? null : base64_encode($row[$k]);

                });
                (new linq(array('IdParamValue', 'IdAuction', 'IdLot', 'IdParam', 'OrderNum')))->for_each(function($k) use (&$row){
                    array_key_exists($k, $row) && ($row[$k] = $row[$k] === null ? null : (int)$row[$k]);

                });
                $k = 'Visible';
                $row[$k] = $row[$k] == '1' ? true : false;
                $k = 'Value';
                $row[$k] && ($row[$k] = base64_encode($row[$k]));
            })->getData();
}
echo json_encode($lot);

?>