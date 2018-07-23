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
$report = array();
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
                . '-' . ($v['d'] < 10 ? '0' : '') . $v['d'] . ($k === 'from' ? "'" : " 23:59:59'");
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

$checkedFields = array('KeyLot' => 'Key', 'VIN' => 'VIN');

$auction_lotes_list = (new linq($db->lot_list->getRows(implode(' AND ', $conditions))))
    ->where(function($lot) use (&$filter, &$checkedFields) {
        if (!$filter) {
            return true;
        }
        return (new linq($checkedFields))->first(function($lotKey, $filterKey) use (&$lot, &$filter){
            $checkedV = $lot[$lotKey] ? $lot[$lotKey] : '';
            $reg = array_key_exists($filterKey, $filter) &&  $filter[$filterKey] ? $filter[$filterKey] : '';
            return !mb_ereg_match(strtolower($reg), strtolower($checkedV));
        })  === null;
        
    })
    ->select(function($lot) use ($db) {
        $lot['Key'] = base64_encode($lot['Key']);
        $lot['VIN'] = $lot['VIN'] ? base64_encode($lot['VIN']) : '';
        $lot['Archive'] = !!$lot['Archive'];
        $l = new lot_list($lot);
        /*
         * Т.к. пользователь может начать редактировать любой лот, то мы здесь 
         * выбираем изображения и параметры независимо от их видимости. 
         * Этот флаг учтем на стороне клиента.
         */
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
        $lot['_params'] = (new linq((new lot_list($lot))->getParams()))
                ->where(function($row){ return $row !== null && count($row) > 0; })
                ->for_each (function(&$row){
                    (new linq(array('Name', 'Caption')))->for_each(function($k) use (&$row){
                        $row[$k] = $row[$k] === null ? null : base64_encode($row[$k]);
                        
                    });
                    (new linq(array('IdParamValue', 'IdAuction', 'IdLot', 'IdParam', 'OrderNum')))->for_each(function($k) use (&$row){
                        $row[$k] = $row[$k] === null ? null : (int)$row[$k];
                        
                    });
                    $k = 'Visible';
                    $row[$k] = $row[$k] == '1' ? true : false;
                })
                ->getData();
        return $lot;
    })->getData();

echo '{"records":';
if (count($auction_lotes_list) > 0) {
    $_key = 'ImagesCount';
    if (array_key_exists($_key, $filter) && $filter[$_key] !== null && $filter[$_key] !== '') {
        /*Отсеиваем по количеству картинок*/
        $f = (function($CSign){
            switch($CSign) {
                case '<': return function($checkPoint, $imagesCount){ return $imagesCount < $checkPoint;};
                case '>': return function($checkPoint, $imagesCount){ return $imagesCount > $checkPoint;};
                case '=': return function($checkPoint, $imagesCount){ return $imagesCount == $checkPoint;};
                case '>=': return function($checkPoint, $imagesCount){ return $imagesCount >= $checkPoint;};
                case '<=': return function($checkPoint, $imagesCount){ return $imagesCount <= $checkPoint;};
                case '<>': return function($checkPoint, $imagesCount){ return $imagesCount != $checkPoint;};
            }
        })($filter['ImagesCount_CSign']);
        $checkPoint = intval($filter[$_key]);
        $auction_lotes_list = (new linq($auction_lotes_list))->where(function($lot) use ($f, $checkPoint) {
            return $f($checkPoint, count($lot['_images']));
        })->getData();
    }
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