<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию об аукционе*/
$key = 'IdAuction';
$auction = null;
if (array_key_exists($key, $_REQUEST)) {
    $IdAuction = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';
    $auction = (new linq($db->auctiones->getRows('`IdAuction`=' . $IdAuction)))->first();
}

/*Получаем состояние фильтра*/
$key = 'filter';
$filter = array();
if (array_key_exists($key, $_REQUEST) && gettype($_REQUEST[$key]) === gettype(array())) {
    $filter = $_REQUEST[$key];
    (new linq(array('KeyLot', 'VIN'))) ->for_each(function($k) use (&$filter){
        array_key_exists($k, $filter) && ($filter[$k] = base64_decode($filter[$k]));
    });
}
else {
    /*По умолчанию выводим информацию за определенный интервал времени, чтобы не выводить сразу много*/
    $today = GlobalVars::defLotFilterSaleDate();
    $filter = array(
        'SaleDate' => array(
            'from' => array(
                'd' => $today['mday'],
                'm' => $today['mon'],
                'y' => $today['year']
            )
        )
    );
}

/*Построим условие для первичной выборки*/
$conditions = array();
$auction !== null && ($conditions[] = '`IdAuction`=' . $auction['IdAuction']);
$key = 'IdAuction';
if (array_key_exists($key, $filter) && $filter[$key]){ 
    $auction = (new linq($db->auctiones->getRows('`Name`=\'' . $filter['IdAuction'] . '\'')))->first();
    if ($auction) {
        $conditions[] = '`IdAuction`=' . $auction['IdAuction'];
    }
    unset($filter[$key]);
}

$key = 'SaleDate';
if (array_key_exists($key, $filter)){ 
    $_cond = (new linq($filter[$key]))->select(function($v, $k) use (&$conditions){
        $res = null;
        if((new linq($v))->firstKey(function($_v){return $_v == '0';})) {
            /*Если дата указана некорректно, не будем ее учитывать*/
        }
        else {
            /*Все элементы даты определены. Строим дату для сравнения*/
            $sign = $k === 'from' ? '>=' : '<=';
            $res = '`SaleDate`' . $sign . "'" . $v['y'] 
                . '-' . ($v['m'] < 10 ? '0' : '') . $v['m'] 
                . '-' . ($v['d'] < 10 ? '0' : '') . $v['d'] . "'";
        }
        return $res;
    })
    ->where(function($c){ return !!$c;})->getData();
    $conditions = array_merge($conditions, $_cond);
    unset($filter[$key]);
}

/*Для полей VIN и номер лота сделаем регулярные выражения для проверки*/
(new linq(array('VIN', 'KeyLot')))->for_each(function($k) use (&$filter){
    array_key_exists($k, $filter) &&
        $filter[$k] &&
        ($filter[$k] = str_replace('*', '.*', str_replace('%', '.', str_replace('.', '\\.', $filter[$k]))));
});

/*Извлекаем записи из БД с учетом состояния фильтра*/

$auction_lotes_list = (new linq($db->lot_list->getRows(implode(' AND ', $conditions))))
    ->where(function($lot) use ($filter) {
        if (!$filter) {
            return true;
        }
        return (new linq($filter))->first(function($reg, $k) use ($lot){
            $checkedV = '';
            switch ($k) {
                case 'KeyLot':
                    $checkedV = $lot['Key'];
                    break;
                case 'VIN':
                    $checkedV = $lot['VIN'] ? $lot['VIN'] : '';
                    break;
            }
            return !mb_ereg_match(strtolower($reg), strtolower($checkedV));
        }) === null;
    })
    ->select(function($lot) use ($db) {
        $lot['Key'] = base64_encode($lot['Key']);
        $lot['VIN'] = $lot['VIN'] ? base64_encode($lot['VIN']) : '';
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

                })
                ->getData();
        return $lot;
    })->getData();

echo '{"records":';
if (count($auction_lotes_list) > 0) {
    echo json_encode($auction_lotes_list);
}
else {
    echo '[]';
}
echo ',"auctiones":';
echo json_encode((new linq($db->auctiones->getRows()))->select(function($a){
    return array(
        'IdAuction' => $a['IdAuction'],
        'Name' => base64_encode($a['Name'])
    );
    
})->getData());
echo '}';

?>