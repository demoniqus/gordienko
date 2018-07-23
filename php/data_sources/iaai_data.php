<?php
/*
 * При сбое загрузки картинок для данного аукциона следует попробовать "поиграть"
 * с запрашиваемым разрешением картинки.
 * Ссылка имеет вид 'https://vis.iaai.com:443/resizer?imageKeys=' . $image_source[$key] . '&width=799&height=599'
 * Также оригинал ссылки можно взять из кода страницы аукциона
 * Предельный доступный размер картинок 799*599, доступен также размер 768*576, 
 * стандарт 640*480. Но ЗАКАЧИВАЕТСЯ 720*540!!
 * Предполагаемый оригинальный размер 1280*960, но доступа к нему нет
 */
$imgW = 799;
$imgH = 599;


/*Функция для раскодирования значения VIN на IAAI*/
$xor = function ($str) {
    $res = '';
    for ($i = 0; $i < strlen($str);$i+= 2) {
        // holds each letter (2 digits)
        $letter = '';
        $letter = $str[$i] . $str[$i + 1];
        // build the real decoded value
        $res .= chr(intval(hexdec($letter)));
    }
    return $res;
    
};


/*
 * Зададим функции, с помощью которых для лота можно определить VIN, дату реализации,
 * марку, модель, год выпуска
 */
$getVIN = function($v, $k){
    $k = strtolower($k);
    return $k === 'vin';
};
$getSaleDate = function($v, $k){ 
    $k = strtolower($k);
    return $k === 'auctiondate';//strpos($k, 'sale') !== false && strpos($k, 'date') !== false;
};
$getMaker = function($v, $k){ 
    $k = strtolower($k);
    return $k === 'asapmake';
};
$getModel = function($v, $k){ 
    $k = strtolower($k);
    return $k === 'asapmodel';
};
$getManufactYear = function($v, $k){ 
    $k = strtolower($k);
    return $k === 'year';
};
/*Зададим функции для разбора сложных параметров*/

$getProperty = function($source, $propName){
    if (gettype($source) === gettype(array())) {
        return array_key_exists($propName, $source) ? $source[$propName] : null;
    }
    if (strtolower(gettype($source)) === 'object') {
        return property_exists($source, $propName) ? $source->$propName : null;
    }
    return $source;
//    return gettype($source) === gettype(array()) ? (array_key_exists($propName, $source) ? $source[$propName] : null ) : 
    //strtolower(gettype($source)) === 'object' ?  (property_exists($source, $propName) ? $source->$propName : null) :
//        null ;
//        $source;
};

$handlers = array(
    'conditioninfo' => function(&$container, $v, $k) use ($getProperty){
        if (gettype($v) !== gettype(array())) {
            return;
        }
        
        (new linq($v))->for_each(function($param) use ($getProperty, &$container){
            $paramV = $getProperty($param, 'DisplayValues');
            if (
                    gettype($paramV) === gettype(array()) &&
                    count($paramV) > 0
                ) {
                $v = (new linq($paramV))->select(function($displayValue) use ($getProperty){
                    $label = $getProperty($displayValue, 'Label');
                    $text = $getProperty($displayValue, 'Text');
                    return ($label ? $label . ' - ' : '') . $text;
                })->getData();
                if ($v && count($v) > 0) {
                    $container[$getProperty($param, 'Name')] = implode('; ', $v);
                }
            }
        });
        
        
//        $param = (new linq($v))->first(function($el) use ($getProperty){ return strtolower($getProperty($el, 'Name')) == 'odometer';});
//        if ($param !== null) {
//            $paramV = $getProperty($param, 'DisplayValues');
//            if (
//                    gettype($paramV) === gettype(array()) &&
//                    count($paramV) > 0
//                ) {
//                $container['Odometer'] = $getProperty($paramV[0], 'Text');
//            }
//        }
//        
//        $param = (new linq($v))->first(function($el) use ($getProperty) {return strtolower($getProperty($el, 'Name')) == 'missingcomponents';});
//        if ($param !== null) {
//            $paramV = $getProperty($param, 'DisplayValues');
//            if (
//                    gettype($paramV) === gettype(array()) &&
//                    count($paramV) > 0
//                ) {
//                $container['MissingComponents'] = join('; ', (new linq($paramV))->select(function($el) use ($getProperty){ return $getProperty($el, 'Text');})->getData());
//            }
//        }
//        
//        $param = (new linq($v))->first(function($el) use ($getProperty) {return strtolower($getProperty($el, 'Name')) == 'airbagdeploymentcheck';});
//        if ($param !== null) {
//            $paramV = $getProperty($param, 'DisplayValues');
//            if (
//                    gettype($paramV) === gettype(array()) &&
//                    count($paramV) > 0
//                ) {
//                (new linq($paramV))->for_each(function($el) use (&$container, $getProperty){ 
//                    $container[$getProperty($el, 'Label')] = $getProperty($el, 'Text');
//                });
//            }
//        }
    },
    'conditionoverviewinfo' => function(&$container, $v, $k) use ($getProperty){
        if (gettype($v) !== gettype(array())) {
            return;
        }
        
        (new linq($v))->for_each(function($param) use ($getProperty, &$container){
            $paramV = $getProperty($param, 'DisplayValues');
            if (
                    gettype($paramV) === gettype(array()) &&
                    count($paramV) > 0
                ) {
                $v = (new linq($paramV))->select(function($displayValue) use ($getProperty){
                    $label = $getProperty($displayValue, 'Label');
                    $text = $getProperty($displayValue, 'Text');
                    return ($label ? $label . ' - ' : '') . $text;
                })->getData();
                if ($v && count($v) > 0) {
                    $container[$getProperty($param, 'Name')] = implode('; ', $v);
                }
            }
        });
        
    },
    'vininfo' => function(&$container, $v, $k) use ($getProperty) {
        if (gettype($v) !== gettype(array())) {
            return;
        }
        
        $simpleParams = array(
        );
        
        (new linq($simpleParams))->for_each(function($paramKeyValue, $paramKey) use ($v, &$container, $getProperty){
            $paramV = (new linq($v))->first(function($elValue, $elKey)use($paramKey){ return trim(strtolower($elKey)) == $paramKey;});
            if ($paramV !== null) {
                $container[$paramKeyValue] = $paramV;
            }
//            $param = (new linq($v))->first(function($el)use($paramKey, $getProperty){ return trim(strtolower($getProperty($el, 'Name'))) == $paramKey;});
//            if ($param !== null) {
//                $paramV = $getProperty($param, 'DisplayValues');
//                if (
//                        gettype($paramV) === gettype(array()) &&
//                        count($paramV) > 0
//                    ) {
//                    
//                    $container[$paramKeyValue] = $getProperty($paramV[0], 'Text');
//                }
//            }
        });
        
        $complexParams = array(
            'manufacturedin' => 'Manufactured In',
            'manufacturedfor' => 'Manufactured For',
            'bodystyle' => 'Body Style',
            'vehicleclass' => 'Vehicle Class',
            'series' => 'Series',
            'engine' => 'Engine',
            'fueltype' => 'Fuel Type',
            'cylinders' => 'Cylinders',
            'transmission' => 'Transmission',
            'drivelinetype' => 'Drive Line Type',
            'brakes' => 'Brakes',
            'color' => 'Color',
            'comfort' => 'Comfort',
            'entertainment' => 'Entertaiment',
            'features' => 'Features',
            'otheroptions' => 'Other Options'
        );
        
        (new linq($complexParams))->for_each(function($paramKeyValue, $paramKey) use ($v, &$container, $getProperty){
            $param = (new linq($v))->first(function($el)use($paramKey, $getProperty){ return trim(strtolower($getProperty($el, 'Name'))) == $paramKey;});
            if ($param !== null) {
                
                $paramV = $getProperty($param, 'DisplayValues');
                if (
                        gettype($paramV) === gettype(array()) &&
                        count($paramV) > 0
                    ) {
                    $v = (new linq($paramV))->select(function($displayValue) use ($getProperty){
                        $label = $getProperty($displayValue, 'Label');
                        $text = $getProperty($displayValue, 'Text');
                        return ($label ? $label . ' - ' : '') . $text;
                    })->getData();
                    if ($v && count($v) > 0) {
                        $container[$getProperty($param, 'Name')] = implode('; ', $v);
                    }
                }
                
                
//                $paramV = $getProperty($param, 'DisplayValues');
//                if (
//                        gettype($paramV) === gettype(array()) &&
//                        count($paramV) > 0
//                    ) {
//                    $container[$paramKeyValue] = join('; ', (new linq($paramV))->select(function($el) use ($getProperty) { return $getProperty($el, 'Text');})->getData());
//                }
            }
        });
        
    },
    'saleinfo' => function(&$container, $v, $k) use ($getProperty) {
        if (gettype($v) !== gettype(array())) {
            return;
        }
        
        $simpleParams = array(
            'slot' => 'Slot',
            'titledocument' => 'Title Document',
            'titlestate' => 'Title State',
            'brand' => 'Brand',
            'notes' => 'Notes',
            'seller' => 'Seller',
            'conditionreport' => 'Condition Report',
            'costofrepairdoc' => 'Cost of Repair Document',
            'acv' => 'ACV',
            'estimatedrepaircost' => 'Estimated Repair Cost'
        );
        (new linq($simpleParams))->for_each(function($paramKeyValue, $paramKey) use ($v, &$container, $getProperty){
            $paramV = (new linq($v))->first(function($elValue, $elKey)use($paramKey){ return trim(strtolower($elKey)) == $paramKey;});
            if ($paramV !== null) {
                $container[$paramKeyValue] = $paramV;
            }
//            $param = (new linq($v))->first(function($el)use($paramKey, $getProperty){ return trim(strtolower($getProperty($el, 'Name'))) == $paramKey;});
//            if ($param !== null) {
//                $paramV = $getProperty($param, 'DisplayValues');
//                if (
//                        gettype($paramV) === gettype(array()) &&
//                        count($paramV) > 0
//                    ) {
//                    $container[$paramKeyValue] = $getProperty($paramV[0], 'Text');
//                }
//            }
        });
        
    }
);



$content = '';
$paramsValues = array();
/*Получим информацию о лоте*/
if ($auction['SyncFromFrame'] || $auction['SyncFromPopupWindow']) {
    /*Синхронизация производится через браузер и сюда передаются уже готовые объекты*/
    $paramsValues = $_POST['lotParams'];
}
else {
    $content = (new URL())->getURLContent('https://www.iaai.com/Vehicle?itemID=' . $keyLot)[0];
    $scripts = array();
    
    preg_match_all('/<script.+?<\/script>/ims', $content, $scripts);
    
    $paramsValues = (new linq($scripts[0]))->first(function($script){
        return preg_match('/id="ProductDetailsVM"/ims', $script) > 0;
    });

    if ($paramsValues !== null) {
        $paramsValues = preg_replace('/<[^<>]*script[^<>]*>/', '', $paramsValues);
        $paramsValues = trim($paramsValues);
        try {
            $paramsValues = (new linq(json_decode($paramsValues)))->getData();
        } catch (Exception $ex) {
            $paramsValues = null;
        }
    }
}



/*Запишем полученные значения параметров лота в БД*/
if (
        $paramsValues !== null && 
        gettype($paramsValues) === gettype(array()) && 
        count($paramsValues) > 0 &&
        array_key_exists('VehicleDetailsViewModel', $paramsValues)
    ) {
    
    /*
     * Обработаем полученные данные и немного отсеим мусор
     */
    $paramsValues = (new linq($paramsValues['VehicleDetailsViewModel'], 'assoc'))->
        reduce(function($container, $v, $k) use ($xor, $handlers){
            $_k = strtolower($k);
            if (strpos($_k, 'tooltip') > -1 ||
                strpos($_k, 'message') > -1 ||
                strpos($_k, 'imageurl') > -1) {
                /*Данные элементы не несут в себе ничего полезного и их не надо сохранять*/
                return $container;
            }
            $type = gettype($v);
            if ($type !== gettype(array()) && strtolower($type) !== 'object') {
                /*Значения примитивных типов сохраняем как есть за исключением VIN, который нужно раскодировать*/
                if ($_k === 'vin') {
                    $v = $xor($v);
                }
                if (!array_key_exists($k, $container) || $container[$k] === null)  {
                    $container[$k] = $v;
                }
                return $container;
            }
            /*Значения сложных типов нужно разбирать каждое по отдельности*/
            if (array_key_exists($_k, $handlers)) {
                $handlers[$_k]($container, $v, $k);
            }
            return $container;
            
        }, array());
        
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
    (new linq($paramsValues))->for_each(function($v, $k) use ($auct_params, $db, $lot){
        /*Проверим, не было ли еще записано какое-либо значение для данного параметра для данного лота*/
        $param = $auct_params[$k];
        $pv = $db->lot_params_values->getFirstRow('`IdLot`=' . $lot['IdLot'] . ' AND `IdParam`=' . $param['IdParam']);
        if ($v !== null && gettype($v) === gettype(array())) {
            $v = serialize($v);
        }
        if ($pv === null) {
            if ($v === null || !preg_replace('/[\s\.,_;\?<>\*+-]/', '', $v)) {
                /*Пустые значения не сохраняем*/
                return;
            }
            $pv = $db->lot_params_values->getEmptyEntity();
            $pv['Value'] = $v;
            $pv['IdLot'] = $lot['IdLot'];
            $pv['IdParam'] = $param['IdParam'];
            $db->lot_params_values->Insert($pv);
        }
        else {
            if ($v === null || !preg_replace('/[\s\.,_;\?<>\*+-]/', '', $v)) {
                /*Пустые значения не сохраняем*/
                $db->lot_params_values->Delete($pv);
            }
            else {
                $pv['Value'] = $v;
                $db->lot_params_values->Update($pv);
            }
        }
    });

    /*Обновим VIN, дату продажи, марку, модель, год выпуска лота*/
    $updatedData = array();
    $saleDate = (new linq($paramsValues))->first($getSaleDate);
//    if (
//            $saleDate !== null && 
//            gettype($saleDate) === gettype(array()) &&
//            gettype($saleDate = (new linq($saleDate))->first(function($v, $k){ return strtolower($k) === 'dateasint';})) === gettype(111)
//        ) {
//        /*Ключ был SaleDate и дата была представлена в виде объекта*/
//        $saleDate = $saleDate . '';
//        if ( preg_match('/^2\d{3}[0-1][0-9][0-3][0-9]$/i', $saleDate) === 1) {
//            $saleDate = substr($saleDate, 0, 4) . '-' . 
//                    substr($saleDate, 4, 2) . '-' . 
//                    substr($saleDate, 6, 2) . ' 00:00:00'; 
//            $updatedData['SaleDate'] = $saleDate;
//
//        }
//    }
    if (
            $saleDate !== NULL &&
            gettype($saleDate) === gettype('aaa')
        ) {
                        /*MMDDYYYY*/
        if ( preg_match('/^[0-1][0-9][0-3][0-9]2[0-9]{3}$/i', $saleDate) === 1) {
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
    
    /*Отметим, что лот найден*/
    $isFound = true;
}
else {
    /*
     * Если не удалось найти блок с параметрами лота, поищем, нет ли указаний на то, что 
     * лот вообще не найден.
     */
    if (
            preg_match('/class="(\.+-)?not-found"/i', $content) ||
            preg_match('/ehicle\s+details\s+are\s+not\s+found/i', $content)
        ) {
        $isFound = false;
    }
}



if ($isFound !== false) {
    /*Теперь обрабатываем изображения*/
    $images = array();
    if ($auction['SyncFromFrame'] || $auction['SyncFromPopupWindow']) {
        /*Синхронизация производится через браузер и сюда передаются уже готовые объекты*/
        $images = $_POST['lotImages'];
    }
    else {
        $images = (new linq($scripts[0]))->first(function($script){
            return preg_match('/"DeepZoomInd"/ims', $script) > 0;
        });

        if ($images !== null) {
            $images = substr($images, strpos(strtolower($images), '"deepzoomind"'));
            $images = substr($images, 0, strpos(strtolower($images), '}\');'));
            /*Т.к. мы срезали скобки и кавычки таким подходом, то нужно их восстановить*/
            $images = '{' . $images . '}';
            try {
                $images = json_decode($images);
            } catch (Exception $ex) {
                $images = null;
            }
        }
    }

    $key = 'keys';
    
    if ($images !== null) {
        if (gettype($images) === gettype(array())) {
            
            if (
                array_key_exists($key, $images) &&
                $images[$key] !== null && 
                gettype($images[$key]) === gettype(array()) && 
                count($images[$key]) > 0
                ) {
                
                $images = $images[$key];
            }
        }
        else {
            if (
                property_exists($images, $key) &&
                $images->$key !== null && 
                gettype($images->$key) === gettype(array()) && 
                count($images->$key) > 0
                ) {
                
                $images = $images->$key;
            }
        }
    }
    
    if ($images !== null && array_key_exists(gettype($images), array( gettype(array()) => null, 'object' => null))) {

        /*
         * Копируем папку для хранения изображений
         */
        $lot = $db->lot_list->getEntity($lot['IdLot']);
        $pictStorage = strtolower($auction['Name']) . DIRECTORY_SEPARATOR . ($lot['SaleDate'] != null ? $lot['SaleDate']->format('Y-m-d') . ' ' : '') . $keyLot;
        
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
        $storagePath = '.' . DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $pictStorage;
        
        /*
         * Проверить существование изображения можно только по ключу, поэтому 
         * нужно выбрать все имеющиеся у лота изображения и проверять их OrigName
         */
        $existsImages = (new linq($db->lot_images->getRows('`IdLot`=' . $lot['IdLot'])))
                ->for_each(function(&$img){
                    $img['OrigName'] && ($img['OrigName'] = base64_decode($img['OrigName']));
                })
                ->getData();
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
                use ($db, $lot, $storagePath, $pictStorage, $imgW, $imgH, $existsImages, $nextfNamePrefix){
            $image_source = (new linq($image_source))->getData();
            $key = 'K';
            if  (!array_key_exists($key, $image_source) || $image_source[$key] == null ) {
                /*Видимо, изменилась структура данных и нужно указывать новый ключ для выборки источника картинки*/
                return;
            }
            $key = $image_source[$key];
            /*Проверим, нет ли еще данной картинки в БД*/
            if (($__img = (new linq($existsImages))->first(function($img) use (&$key){ return strpos($img['OrigName'], $key) !== false; })) === null) {
                $url = 'https://vis.iaai.com:443/resizer?imageKeys=' . $key . '&width=' . $imgW . '&height=' . $imgH;
                /*Если такой картинки еще нет, загрузим ее*/
                /*Определим имя, под которым сохраним изображение в хранилище*/
                $fileName = $nextfNamePrefix() . $key . '.jpg';
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
                } 
                catch (Exception $ex) {
                }
            }
        });
    }
}
/*выводим метку, что процесс обновления данного лота завершен*/
if ($isFound !== false) {
    echo '{"syncStatus":true}';
}
else {
    echo '{"syncStatus":false,"message":"В источнике нет данных для лота ' . $lot['Key'] . '","key":"' . base64_encode($lot['Key']) . '"}';
}



?>