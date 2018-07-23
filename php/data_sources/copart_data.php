

<?php
/*
 * Зададим функции, с помощью которых для лота можно определить VIN, дату реализации,
 * марку, модель, год выпуска
 */
$getVIN = function($v, $k){
    $k = strtolower($k);
    return $k === 'fv' || (strpos($k, 'full') !== false && strpos($k, 'vin') !== false);
};
$getSaleDate = function($v, $k){ 
    $k = strtolower($k);
    return strpos($k, 'auction') !== false && strpos($k, 'date') !== false;
};
$getMaker = function($v, $k){ 
    $k = strtolower($k);
    return $k == 'mkn';
};
$getModel = function($v, $k){ 
    $k = strtolower($k);
    return $k == 'lm';
};
$getManufactYear = function($v, $k){ 
    $k = strtolower($k);
    return $k == 'lcy';
};

$urlInstance = new URL();

/*Загружаем параметры лота с разных ресурсов в один массив*/
$paramsValues = array();
if ($auction['SyncFromFrame'] || $auction['SyncFromPopupWindow']) {
    /*Синхронизация производится через браузер и параметры передаются в готовом виде*/
    $paramsValues = $_POST['lotParams'];
}
else {
    $urlInstance->setOption(CURLOPT_COOKIESESSION, true);
    $urlInstance->setOption(CURLOPT_COOKIEFILE, '');
    /*Сначала требуется получить cookies*/
    $baseURL = 'https://www.copart.com/lot/' . $keyLot;
    $baseRequest = $urlInstance->getURLContent($baseURL);
    $urlInstance->setOption(CURLOPT_COOKIESESSION, false);
    $paramsValues = (new linq(array(
        /*
        * Данные для аукциона copart можно получить из двух источников. Эти источники описаны в папке dev_info
        */
        'https://www.copart.com/public/data/lotdetails/dynamic/' . $keyLot,
        'https://www.copart.com/public/data/lotdetails/solr/' . $keyLot
    )))
    ->reduce(
        function($container, $url) use ($urlInstance){
            $key = 'data';
            $key2 = 'lotDetails';
            $pv = json_decode((new URL())->getURLContent($url)[0], true);
            if (
                $pv !== null && 
                gettype($pv) === gettype(array()) && 
                count($pv) > 0 &&
                array_key_exists($key, $pv) &&
                $pv[$key] !== null && 
                gettype($pv[$key]) === gettype(array()) && 
                count($pv[$key]) > 0 && 
                array_key_exists($key2, $pv[$key]) &&
                $pv[$key][$key2] !== null && 
                gettype($pv[$key][$key2]) === gettype(array()) && 
                count($pv[$key][$key2]) > 0 
            ) {
                $container = array_merge($container, $pv[$key][$key2]);
            }
            return $container;
        },
        array()
    );
}



/*Запишем полученные значения параметров лота в БД*/
if (
        $paramsValues !== null && 
        gettype($paramsValues) === gettype(array()) && 
        count($paramsValues) > 0 &&
        !(array_key_exists('errorMsg', $paramsValues))
    ) {

    /*
     * Получим список уже имеющихся в аукционе параметров и, если найдены новые, 
     * обновим общий список параметров аукциона
     */
    $auct_params = (new linq($db->auction_params->getRows('`IdAuction`=' . $auction['IdAuction'])))
        ->toAssoc(function($param){ return $param['Name'];})
        ->getData();

    (new linq($paramsValues))->for_each(function($v, $k) use ($auct_params, $auction, $db){
        if (!array_key_exists($k, $auct_params)) {
            /*Такой параметр еще не существует. Нужно его создать*/
            $newEntity = $db->auction_params->getEmptyEntity(array(
                'IdAuction' => $auction['IdAuction'],
                'Name' => $k,
                'Visible' => true,
                'OrderNum' => 0
            ));
            $db->auction_params->Insert($newEntity);
        }
    });

    /*
     * Теперь для аукциона заведен полный список доступных параметров. 
     * Обновим справочник идентификаторов параметров, 
     * чтобы сохранить значения параметров конкретного лота в БД
     */

    $auct_params = (new linq($db->auction_params->getRows('`IdAuction`=' . $auction['IdAuction'])))
        ->toAssoc(function($param){ return $param['Name'];})
        ->getData();
    /*
     * Теперь имеются идентификаторы всех параметров и можно сохранять значения
     */
    
    (new linq($paramsValues))->for_each(function($v, $k) use ($auct_params, $auction, $db, $lot, &$report){
        
        /*Проверим, не было ли еще записано какое-либо значение для данного параметра для данного лота*/
        $param = $auct_params[$k];
        $pv = $db->lot_params_values->getFirstRow('`IdLot`=' . $lot['IdLot'] . ' AND `IdParam`=' . $param['IdParam']);
        if ($v !== null && gettype($v) === gettype(array())) {
            $v = serialize($v);
        }
        $r = array (
            'valueKey' => $k,
            'value' => $v,
            'pv' => $pv,
            'param' => $param
        );
        if ($pv === null) {
            if ($v === null || !preg_replace('/[\s\.,_;\?<>\*+-]/', '', $v)) {
                /*Пустые значения не сохраняем*/
                return;
            }
            $pv = $db->lot_params_values->getEmptyEntity();
            $pv['Value'] = $v;
            $pv['IdLot'] = $lot['IdLot'];
            $pv['IdParam'] = $param['IdParam'];
            $r['newPV'] = $db->lot_params_values->Insert($pv);
        }
        else {
            if ($v === null || !preg_replace('/[\s\.,_;\?<>\*+-]/', '', $v)) {
                /*Пустые значения не сохраняем*/
                $db->lot_params_values->Delete($pv);
            }
            else {
                $pv['Value'] = $v;
                $r['updatedPV'] = $db->lot_params_values->Update($pv);
            }
        }
        $report[] = $r;
    });

    /*Обновим VIN, дату продажи, марку, модель, год выпуска лота*/
    $updatedData = array();
    $saleDate = (new linq($paramsValues))->first($getSaleDate);
    if (
            $saleDate !== null
        ) {
                
        if (
            gettype($saleDate) === gettype(array()) &&
            gettype($saleDate = (new linq($saleDate))->first(function($v, $k){ return strtolower($k) === 'dateasint';})) === gettype(1111)
            ) {

            $saleDate = $saleDate . '';
            if ( preg_match('/^2\d{3}[0-1][0-9][0-3][0-9]$/i', $saleDate) === 1) {
                $saleDate = substr($saleDate, 0, 4) . '-' . 
                        substr($saleDate, 4, 2) . '-' . 
                        substr($saleDate, 6, 2) . ' 00:00:00'; 
                $updatedData['SaleDate'] = $saleDate;

            }
        } 
        else if (
                gettype($saleDate) === gettype('aaa') &&
                preg_match('/^[0-1][0-9][0-3][0-9]2\d{3}$/i', $saleDate) === 1
            ) {
            $saleDate = substr($saleDate, 4, 4) . '-' . 
                    substr($saleDate, 0, 2) . '-' . 
                    substr($saleDate, 2, 2) . ' 00:00:00'; 
            $updatedData['SaleDate'] = $saleDate;
        }
    }
    $VIN = (new linq($paramsValues))->first($getVIN);
    if ($VIN !== null) {
        $updatedData['VIN'] = $VIN;
    }
    $maker = (new linq($paramsValues))->first($getMaker);
    if ($maker !== null) {
        $updatedData['Maker'] = base64_encode($maker);
    }
    $model = (new linq($paramsValues))->first($getModel);
    if ($model !== null) {
        $updatedData['Model'] = base64_encode($model);
    }
    $manufactYear = (new linq($paramsValues))->first($getManufactYear);
    if ($manufactYear !== null) {
        $updatedData['ManufactYear'] = $manufactYear;
    }
    
    if (count($updatedData) > 0) {
        $updatedData['IdLot'] = $lot['IdLot'];
        $db->lot_list->Update($updatedData);
    }
    $isFound = true;
}
else {
    if (array_key_exists('', $paramsValues)) {
        $isFound = false;
    }
}









if ($isFound !== false) {
    /*
     * Значения параметров лота сохранены. Теперь выкачиваем изображения.
     */
    $images = array();
    /*Сначлала получим список изображений*/
    if ($auction['SyncFromFrame'] || $auction['SyncFromPopupWindow']) {
        $images = $_POST['lotImages'];
    }
    else {
        $imagesUrl = 'https://www.copart.com/public/data/lotdetails/lotImages/' .$keyLot;

        $images = json_decode($urlInstance->getURLContent($imagesUrl)[0], true);

        $pathToImage = array('data', 'imagesList');

        $images = (new linq($pathToImage))->reduce(function($images, $key){
            if ($images !== null) {
                if (gettype($images) === gettype(array())) {
                    if (
                        array_key_exists($key, $images) 
                    ) {
                        $images = $images[$key];
                    }
                    else {
                        $images = null;
                    }
                }
                else if (
                    gettype($images) === 'object'
                    ){
                    if (
                        property_exists($images, $key) 
                    ) {
                        $images = $images->$key;
                    }
                    else {
                        $images = null;
                    }
                }
                else {
                    $images = null;
                }
            }
        }, $images);

        if ($images !== null && array_key_exists(gettype($images), array( gettype(array()) => null, 'object' => null))) {
            /*Отбираем ссылки на полноразмерные картинки*/
            $images = (new linq($images[$key]))
                ->where(function($image){
                    $key = 'imageType';
                    return array_key_exists($key, $image) && $image[$key] !== null && strtolower($image[$key]) === 'f';
                })
                ->getData();
        }
    }
    
    
    if ($images !== null && array_key_exists(gettype($images), array( gettype(array()) => null, 'object' => null))) {
        /*
         * Копируем папку для хранения изображений
         */
        if (file_exists('.' . DIRECTORY_SEPARATOR . $pictStorage)) {
            FileSystem::copyDir(
                '.' . DIRECTORY_SEPARATOR . $pictStorage, 
                targetDirectory($pictStorage)
            );
        }
        else {
            FileSystem::createDir( 
                targetDirectory($pictStorage)
            );
        }
        /*
        * Определяем путь для сохранения изображений
        */
        $storagePath = targetDirectory($pictStorage);
        
        /*
         * Метод для определения префикса графического файла
         * Префикс используется для вновь создаваемого лота или 
         * при принудительной пересинхронизации
         */
        $usefNamePrefix = $IsNewLot || $forceSync;
        $fNamePrefixIndex = 1;
        $nextfNamePrefix = function() use ($usefNamePrefix, &$fNamePrefixIndex){
            if (!$usefNamePrefix) {
                return '';
            }
            $prefix = strval($fNamePrefixIndex++);
            while (strlen($prefix) < 3) {
                $prefix = '0' . $prefix;
            }
            return $prefix . '_';
        };

        (new linq($images))->for_each(function($image_source) 
                use ($db, $lot, $storagePath, $pictStorage, &$report, $nextfNamePrefix){

            $key = 'url';
            if  (!array_key_exists($key, $image_source) || $image_source[$key] == null ) {
                /*Видимо, изменилась структура данных и нужно указывать новый ключ для выборки источника картинки*/
                return;
            }
            $url = $image_source[$key];
            $condition = '`IdLot`=' . $lot['IdLot'] . ' AND (`OrigName`=\'' 
                . base64_encode($url) . '\' OR `OrigName`=\'' . base64_encode(basename($url)) . '\')';
            /*Проверим, нет ли еще данной картинки в БД*/
            if ($db->lot_images->getFirstRow($condition) === null) {
                /*Если такой картинки еще нет, загрузим ее*/
                /*Определим имя, под которым сохраним изображение в хранилище*/
                $fileName = $nextfNamePrefix() . basename($url);
                try {
                    $urlInstance = new URL();
                    $urlInstance->setOption(CURLOPT_HEADER, 0);
                    $urlInstance->setOption(CURLOPT_RETURNTRANSFER, 1);
                    $urlInstance->setOption(CURLOPT_BINARYTRANSFER, 1);
                    $fileContent = $urlInstance->getURLContent($url);
                    /*
                     * На случай, если что-то пойдет не по плану при закачке изображения,
                     * ограничим минимальный размер
                     */
                    if (strlen($fileContent[0]) > GlobalVars::$minPictureUploadSize) {
                        $handle = fopen($storagePath . DIRECTORY_SEPARATOR . $fileName, 'wb');
                        if (fwrite($handle, $fileContent[0]) != false) {
                            /*Сразу определим размеры изображения для использования в будущем при показе*/
                            $image_info = getimagesize($storagePath . DIRECTORY_SEPARATOR . $fileName);
                            /*Запись на диск произведена успешно. Теперь делаем запись в БД*/
                            $db->lot_images->Insert($db->lot_images->getEmptyEntity(array(
                                'OrigName' => base64_encode($url),
                                'FileName' => base64_encode($pictStorage . DIRECTORY_SEPARATOR . $fileName),
                                'IdLot' => $lot['IdLot'],
                                'Visible' => true,
                                'IsMain' => 0,
                                'Width' => $image_info[0],
                                'Height' => $image_info[1]
                            )));
                        }
                        else {
                            try {
                                /*Удаляем пустой файл, в который не удалось произвести запись*/
                                unlink($storagePath . DIRECTORY_SEPARATOR . $fileName);
                            } catch (Exception $ex) {

                            }
                        }
                    }
                    else {
                    }
                } catch (Exception $ex) {
                }
            }
        });
    }
}
/*выводим метку, что процесс обновления данного лота завершен*/
if ($isFound !== false) {
    echo '{"syncStatus":true,"report":' . json_encode($report) . '}';
}
else {
    echo '{"syncStatus":false,"message":"В источнике нет данных для лота ' . $lot['Key'] . '","key":"' . base64_encode($lot['Key']) . '"}';
}

?>