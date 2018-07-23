<?php
/*
 * Главный файл с подключаемыми модулями
 */
/*
 * Устанавливаем кодировку данных
 */
header('Content-Type: text/html; charset=utf-8');

require_once 'base64.php';
require_once 'db.php';
require_once 'linq.php';
require_once 'types_processor.php';
require_once 'file_system.php';
require_once 'url.php';

/*Глобальные переменные*/
class GlobalVars {
    static $host = 'localhost';
    static $dbName = 'testphp';
    static $hostUser = 'root';
    static $hostPass = 'root';
    static $dataModeKey = 'mode';
    static $dataKeyKey = 'datakey';
    static $requiredPageKey = 'page';
    static $defDirAccess = 0777;
    static $lotDataDir = 'lot_data_storage';
    static $lotDataDirFullName = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lot_data_storage';
    static function defLotFilterSaleDate() {
        /*
         * Чтобы не выводить сразу большой список лотов,
         * введем ограничение по дате реализации - 
         * будем показывать за последние 30 дней
         */
        return getdate(time() - 36 * 30 * 24 * 60 * 60);
    }
}
?>

