<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию о лоте*/
$lot = $db->lot_list->getEmptyEntity();

(new linq($lot))->for_each(function($v, $k) use (&$lot){
    if (array_key_exists($k, $_REQUEST)) {
        $lot[$k] = $_REQUEST[$k];
    }
});

(new linq(array('Key', 'VIN', 'Maker', 'Model')))->for_each(function($k) use (&$lot){
    if (array_key_exists($k, $lot)) {
        $lot[$k] = base64_decode($lot[$k]);
    }
});

/*На основании ключа проверим, нет ли уже такого лота*/
$lots = $db->lot_list->getRows('`IdAuction=`' . 
    preg_replace(StandPrototype::$regexp['safeSQLValue'], '', $lot['IdAuction'] ? $lot['IdAuction'] : '0') . 
    ' AND `Key`=\'' 
    . preg_replace(StandPrototype::$regexp['safeSQLValue'], '', $lot['Key'] ? $lot['Key'] : '') . '\''
);
if (count($lots) > 0) {
    /*Лот с таким ключом уже существует - возьмем его Id*/
    $lot['IdLot'] = $lots[0]['IdLot'];
    /*И проверим, не пытаемся ли мы */
}

$IsNewLot = false;
if ($lot !== null) {
    (new linq(array('SaleDate')))->for_each(function($k) use (&$lot){
        if (array_key_exists($k, $lot) && $lot[$k]) {
            if (gettype($lot[$k]) === gettype(array())) {
                if (array_key_exists('date', $lot[$k])) {
                    $lot[$k] = $lot[$k]['date'];
                }
                else {
                    $lot[$k] = NULL;
                }
            }
        }
    });
    if (preg_match('/^\d+$/', $lot['IdLot'])) {

        $lot = new lot_list($lot);
        if ((int)$lot->IdLot < 1) {
            $lot->Insert();
            $IsNewLot = true;
        }
        else {
            $lot->Update();
        }
    }
    else {
        $lot = null;
    }
}


if ($lot) {
    $auction = $db->auctiones->getEntity($lot->IdAuction);
    if ($auction) {
        /*Создадим папку для хранения файлов*/
        $pictStorage = FileSystem::getPictStoragePath($auction, $lot->Key);
        FileSystem::createDir(GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $pictStorage);
        if ($IsNewLot) {
             $db->lot_list->Update(array(
                'IdLot' => $lot->IdLot,
                'DataFolder' => base64_encode($pictStorage)
            ));
        }

        /*Лот создали / обновили - теперь обработаем изображения и параметры*/
        $key = '_params';
        if (array_key_exists($key, $_REQUEST) && gettype($_REQUEST[$key]) == gettype(array())) {
            (new linq($_REQUEST[$key]))->for_each(function($paramData) use ($lot) {
                $paramData['IdLot'] = $lot->IdLot;
                $paramData['Value'] && ($paramData['Value'] = base64_decode($paramData['Value']));
                $param = new lot_params_values($paramData);
                if ((int)$param->IdParamValue < 1) {
                    $param->Insert();
                }
                else {
                    $param->Update();
                }
            });
        }

        $key = '_images';
        if (array_key_exists($key, $_REQUEST) && gettype($_REQUEST[$key]) == gettype(array())) {
            (new linq($_REQUEST[$key]))->for_each(function($imgData) use ($lot, $pictStorage, $db) {
                $imgData['IdLot'] = $lot->IdLot;
                $key = 'Key';
                if (array_key_exists($key, $imgData)) {

                    $sesKey = base64_decode($imgData[$key]);
                    $sesImgData = $_SESSION['uploaded_files'][$sesKey];
                    $imgData['OrigName'] = $sesImgData['OrigName'];
                    $imgData['FileName'] = $pictStorage . DIRECTORY_SEPARATOR . $imgData['OrigName'];
                    $toFile = GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $imgData['FileName'];
                    if (FileSystem::copyFile($sesImgData['FileName'], $toFile, true)) {
                        $imgSizeInfo = getimagesize($toFile);
                        $imgData['Width'] = $imgSizeInfo[0];
                        $imgData['Height'] = $imgSizeInfo[1];
                        $img = new lot_images($imgData);
                        if ($img->Insert()) {
                            unset($_SESSION['uploaded_files'][$sesKey]);
                            unset($_SESSION['pathKeys'][$sesKey]);
                        }
                    }
                }
                else {
                    $key = '_deleted';
                    if (array_key_exists($key, $imgData) && ($imgData[$key] === '1' || strtolower($imgData[$key]) === 'true')) {
                        $db->lot_images->getObject($imgData['IdImage'])->Delete();
                    }
                    else {
                        $img = new lot_images($imgData);
                        $img->Update();
                    }
                }
            });
        }
        /*Генерируем PDF*/
        if ($IsNewLot) {
            $lot->Base64EncodeFields();
            $images = $lot->getImages(true);

            (new linq($images))->for_each(function($img, $k) use (&$images) {
                $images[$k]['FileName'] = realpath('.' . 
                    DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . 
                    DIRECTORY_SEPARATOR .  base64_decode($img['FileName'])
                );
            });

            $params = $lot->getParams(true);

            $file = '.' . DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . $pictStorage . DIRECTORY_SEPARATOR . PDF::getStandartLotPDFFileName($db->lot_list->getEntity($lot->IdLot));
            
            /*Подключаем генератор PDF*/
            require_once './php/pdf.php';
            /*Генерируем PDF*/
            require_once strtolower($auction['Name']) . '_lot_print_data.php';
            $lot->Base64DecodeFields();
        }
    }
    else {
        $lot = null;
    }
}


if ($lot !== null) {
    $lot->Base64EncodeFields();
    $lot = $lot->getFields();
    (new linq($lot))
        ->for_each(function(&$v, $k) use (&$lot){ 
            array_key_exists($k, array('VIN' => true, 'Key' => true)) && gettype($v) === gettype('') && ($lot[$k] = base64_encode($v));
        });
    $lot['_images'] = (new linq($db->lot_images->getRows('`Visible`=1 AND `IdLot`=' . $lot['IdLot'])))
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
    $lot['_params'] = (new linq($db->query('select ap.*, lpv.IdParamValue, lpv.Value from (select * from lot_params_values where `IdLot`=' . 
            $lot['IdLot'] . ') as lpv left join (select * from auction_params where `IdAuction`=' . 
            $lot['IdAuction'] . ' AND `Visible`=1) as ap on lpv.IdParam = ap.IdParam WHERE ap.IdParam IS NOT NULL')))
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

            })->getData();
    
    echo json_encode($lot);
}
else {
    echo '{"error":"Невозможно сохранить лот. Проверьте правильность заполнения всех полей"}';
}

?>