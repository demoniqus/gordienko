

<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);



/*Получим информацию об аукционе*/
$key = 'IdAuction';
$IdAuction = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';

$auction = (new linq($db->auctiones->getRows('IdAuction=' . $IdAuction)))->first();
$auction_lotes_list = array();
if ($auction) {
    $rootCatName = strtolower($auction['Name']);
    switch ($rootCatName) {
        case 'copart':
            /*
             * Получим список URL, с которых надо загрузить данные. 
             * Т.к. скрипт подключается на верхнем уровне проекта,
             * это влияет на указание пути
             */
            $urlList = FileSystem::getFiles('./' . $rootCatName, true, false, true, '*.url');
            $auction_lotes_list = (new linq($urlList))->select(function($sourceName){
                return array(
                    'sourceFile' => $sourceName,
                    'url' => trim(substr(file($sourceName)[1], 4))
                );
            })->select(function($url) use ($auction, $db, $rootCatName) {
                /*Из URL вычисляем код лота*/
                $urlParts = explode('/', $url['url']);
                $keyLot = $urlParts[count($urlParts) - 1];
                /*Получим информацию о лоте, если такая имеется в БД*/
                $lot = (new linq($db->lot_list->getRows('Key=\'' . trim($keyLot) . '\'')))->first();
                if (!$lot) {
                    $lot = $db->lot_list->getEmptyEntity(array(
                        'Key' => trim($keyLot),
                        'IdAuction' => $auction['IdAuction'] . ''
                    ));
                }
                $lot['_dataURL'] = $url['url'];
                /*Также записываем папку, которая станет хранилищем загруженной информации о лоте*/
                $storagePathParts = explode(strpos($url['sourceFile'], '/') !== false ? '/' : '\\', $url['sourceFile']);
                $partIndex = (new linq($storagePathParts))->firstKey(function($v, $k){
                    return strtolower($v) === 'copart';
                });
                $lot['DataFolder'] = implode(
                    DIRECTORY_SEPARATOR, 
                    (new linq($storagePathParts))->where(function($v, $k) use ($partIndex, $storagePathParts) {
                        return $k >= $partIndex && ($k < count($storagePathParts) - 1);
                    })->getData()
                );
                return $lot;
            })->for_each(function(&$lot){
                foreach ($lot as &$v) {
                    gettype($v) === gettype('') && ($v = base64_encode($v));
                }
            })->getData();
            
            break;
        case 'iaai':
            /*
             * Получим список URL, с которых надо загрузить данные. 
             * Т.к. скрипт подключается на верхнем уровне проекта,
             * это влияет на указание пути
             */
            $urlList = FileSystem::getFiles('./' . $rootCatName, true, false, true, '*.url');
            $auction_lotes_list = (new linq($urlList))->select(function($sourceName){
                return array(
                    'sourceFile' => $sourceName,
                    'url' => trim(substr(file($sourceName)[1], 4))
                );
            })->select(function($url) use ($auction, $db, $rootCatName) {
                /*Из URL вычисляем код лота*/
                
                $keyLot = strtolower($url['url']);
                $keyLot = substr($keyLot, strpos($keyLot, 'itemid='));
                $keyLot = substr($keyLot, strpos($keyLot, '=') + 1);
                if (strpos($keyLot, '&') > -1) {
                    $keyLot = substr($keyLot, 0, strpos($keyLot, '&'));
                }
                
                /*Получим информацию о лоте, если такая имеется в БД*/
                $lot = (new linq($db->lot_list->getRows('Key=\'' . trim($keyLot) . '\'')))->first();
                if (!$lot) {
                    $lot = $db->lot_list->getEmptyEntity(array(
                        'Key' => trim($keyLot),
                        'IdAuction' => $auction['IdAuction'] . ''
                    ));
                }
                $lot['_dataURL'] = $url['url'];
                /*Также записываем папку, которая станет хранилищем загруженной информации о лоте*/
                $storagePathParts = explode(strpos($url['sourceFile'], '/') !== false ? '/' : '\\', $url['sourceFile']);
                $partIndex = (new linq($storagePathParts))->firstKey(function($v, $k){
                    return strtolower($v) === 'iaai';
                });
                $lot['DataFolder'] = implode(
                    DIRECTORY_SEPARATOR, 
                    (new linq($storagePathParts))->where(function($v, $k) use ($partIndex, $storagePathParts) {
                        return $k >= $partIndex && ($k < count($storagePathParts) - 1);
                    })->getData()
                );
                return $lot;
            })->for_each(function(&$lot){
                foreach ($lot as &$v) {
                    gettype($v) === gettype('') && ($v = base64_encode($v));
                }
            })->getData();
            
            break;
    }
}
echo '{"auction_lotes_list":';
if (count($auction_lotes_list) > 0) {
    echo json_encode($auction_lotes_list);
    
}
else {
    echo '[]';
}
echo '}';

?>