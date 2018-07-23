<?php
/*
 * Главный файл с подключаемыми модулями
 */
/*
 * Устанавливаем кодировку данных
 */
header('Content-Type: text/html; charset=utf-8');

require_once 'base64.php';
require_once 'classes.php';
require_once 'db.php';
require_once 'pdf.php';
require_once 'linq.php';
require_once 'types_processor.php';
require_once 'file_system.php';
require_once 'url.php';

/*Глобальные переменные*/
class GlobalVars {
    /*Наименование хоста, на котором располагается БД*/
    static $host = 'localhost';
    /*Наименование БД*/
    static $dbName = 'gordienko';
    /*Логин и пароль для подключения к БД*/
    static $hostUser = 'root';
    static $hostPass = '';
    /*Наименование параметра, определяющего тип запроса - получение данных или получение html-страницы*/
    static $dataModeKey = 'mode';
    /*Наименование параметра, определяющего запрашиваемые данные*/
    static $dataKeyKey = 'datakey';
    /*Наименование параметра, определяющего запрашиваемую html-страницу*/
    static $requiredPageKey = 'page';
    /*
     * Уровень прав для вновь создаваемых каталогов: 
     * 0777 полный доступ на чтение и изменение каталога всеми пользователями 
     * и группами пользователей
     */
    static $defDirAccess = 0777;
    /*
     * Наименование корневого каталога для размещения хранимой информации о лотах.
     * Значение должно быть относительным к рабочему каталогу проекта.
     * Изменяя этот параметр, можно определить другое место для размещения данных.
     */
    static $lotDataDir = 'lot_data_storage';
    /*
     * Временный каталог проекта
     */
    static $tmpDirName = 'tmp';
    /*
     * Минимальный размер файла, который может считаться полноценным изображением лота
     */
    static $minPictureUploadSize = 10240;
    static function defLotFilterSaleDate() {
        /*
         * Чтобы не выводить сразу большой список лотов,
         * введем ограничение по дате реализации - 
         * будем показывать за последние 30 дней
         */
        return getdate(time() - 12*30/*дн*/ * 24/*час*/ * 60/*мин*/ * 60/*сек*/);
    }
    static function tempFilesMaxDate() {
        /*
         * Чтобы временная папка не забивалась файлами, установим максимальное время существования 
         * в ней любых файлов.
         */
        return time() - 2/*дн*/ * 24/*час*/ * 60/*мин*/ * 60/*сек*/;
    }
}
?>

