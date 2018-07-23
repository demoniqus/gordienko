<?php
/*
 * Класс для работы с файловой системой
 */
class FileSystem {
    protected static function safeRemovePath() {
        return array(
            realpath('.' . DIRECTORY_SEPARATOR . GlobalVars::$tmpDirName) => true,
            realpath('.' . DIRECTORY_SEPARATOR . GlobalVars::$lotDataDir) => true
        );
    }
    
    public static function getPath($pathParts) {
        return join(DIRECTORY_SEPARATOR, $pathParts);
    }
    
    public static function getUniqueKey() {
        !array_key_exists('pathKeys', $_SESSION) && ($_SESSION['pathKeys'] = array());
        $randKey = null;
        while (array_key_exists(($randKey = bin2hex(random_bytes('10'))), $_SESSION)) {
            true;
        }
        $_SESSION['pathKeys'][$randKey] = null;
        return $randKey;
    }
    
    public static function createDir($dir) {
        if (!is_dir($dir)) {
            (new linq(explode('/', str_replace('\\', '/',$dir))))->reduce(function($res, $el, $index, $array){
                $res .= $el . DIRECTORY_SEPARATOR;
                if (!is_dir($res)) {
                    mkdir($res, GlobalVars::$defDirAccess);

                }
                return $res;
            }, '');
        }
    }
    
    public static function getPictStoragePath($auction, $keyLot) {
        return strtolower($auction['Name']) . DIRECTORY_SEPARATOR . $keyLot;
    }
    
    public static function copyDir($fromDir, $toDir, $sourceRemove = false) {
        if (!$fromDir || !$toDir) {
            return;
        }
        if (!is_dir($fromDir)) {
            return;
        }
        self::createDir($toDir);
        
        $files = self::getFiles($fromDir, false);
        
        (new linq($files))->for_each(function($fileName) use ($fromDir, $toDir){
            /*Производим копирование файлов*/
            $fileName = substr($fileName, 2);
            $dir = $toDir . DIRECTORY_SEPARATOR . str_replace(basename($fileName), '', $fileName);
            self::createDir($dir);
            copy($fromDir . DIRECTORY_SEPARATOR . $fileName, $toDir . DIRECTORY_SEPARATOR . $fileName);
        });
        if ($sourceRemove) {
            (new linq($files))->for_each(function($fileName){
                if (file_exists($fileName)) {
                    self::Remove($fileName);
                }
            });
        }
    }
    
    public static function copyFile($fromFile, $toFile, $sourceRemove = false) {
        $res = false;
        if (is_file($fromFile) && $toFile) {
            $toDir = str_replace(basename($toFile), '', $toFile);
            self::createDir($toDir);
            $res = copy($fromFile, $toFile);
            if ($sourceRemove) {
                self::Remove($fromFile);
            }
        }
        return $res;
    }
    
    public static function getFiles($rootDir, $useBaseDir = false, $includeDirs = false, $recursive = true, $mask = null) {
        return self::_getFiles($rootDir, $useBaseDir, $includeDirs, $recursive, 0, $mask);
    }
    
    private static function _getFiles($rootDir, $useBaseDir = false, $includeDirs = false, $recursive = true, $deep = 0, $mask = null) {
        $rootDir[strlen($rootDir) - 1] !== '/' && $rootDir[strlen($rootDir) - 1] !== '\\' && ($rootDir .= DIRECTORY_SEPARATOR);
        
        $directories = array();
        $files = array();
        $templates = null;
        if ($mask) {
            /*Если задана маска, то проверим файлы по маске*/
            if (gettype($mask) === gettype('')) {
                $templates = explode(",", $mask);
                $templates = new linq((new linq($templates))->select(function($fMask){
                    return '/^' . str_replace('?', '.?', str_replace('*', '.*', str_replace('.', '\\.', str_replace('\\', '\\\\', trim($fMask))))) . '$/i';
                })->getData());
                /*
                 * Чтобы при рекурсивном поиске не тратить время на повторное 
                 * выполнение преобразования шаблона поиска,
                 * будем дальше передавать уже обработанную коллекцию
                 */
                $mask = $templates;
            }
            else {
                $templates = $mask;
            }
        }
        $realRootPath = realpath($rootDir) . DIRECTORY_SEPARATOR;
        /*Сканируем директорию*/
        (new linq(scandir($rootDir)))->for_each(
            function($el) use ($rootDir, &$files, &$directories, $templates, $realRootPath, $includeDirs){
                if ($el === '.' || $el === '..') {
                    return;
                }
                $_path = $path = $rootDir . $el;
                
                if (is_file($path) || $includeDirs) {
                    /*Если получен файл или имеется указание на запоминание директорий, запоминаем их, делая их путь относительным*/
                    $path = str_replace($realRootPath, '', realpath($path));

                    if (!$templates) {
                        $files[] = $path;
                    }
                    else {
                        /*Проверяем файл по маске*/
                        $found = $templates->first(function($pattern) use ($el) {
                            return preg_match($pattern, $el) > 0;
                        });

                        if ($found !== null) {
                            $files[] = $path;
                        }
                    }
                }
                if (is_dir($_path)) {
                    /*Если получена директория, ее будем сканировать тоже*/
                    $directories[] = $_path;
                }
            }
        );
        /*Спускаемся рекурсивно*/
        if ($recursive) {
            (new linq($directories))->for_each(function($dirName) use (&$files, $mask, $deep, $useBaseDir, $includeDirs, $recursive){
                (new linq(self::_getFiles($dirName, $useBaseDir, $includeDirs, $recursive, $deep + 1, $mask)))
                    ->for_each(function($fileName) use (&$files, $dirName){
                        $fileName = basename($dirName) . DIRECTORY_SEPARATOR . $fileName;
                        $files[] = $fileName;
                    });
            });
        }
        if ($deep === 0) {
            $pathPrefix = '.' . DIRECTORY_SEPARATOR . ($useBaseDir ? basename($rootDir) . DIRECTORY_SEPARATOR : '');
            
            foreach($files as $k => $v) {
                $files[$k] = $pathPrefix . $v;
            }
        }
        return $files;
    }
    
    /*
     * $forceRemove - удаление без проверки, что файл расположен в тех каталогах, из которых разрешено удаление
     */
    public static function Remove($path, $forceRemove = false) {
        if (!file_exists($path)) {
            return;
        }
        /*Проверим, находится ли файл в тех каталогах, из которых разрешено удаление*/
        $canDelete = false;
        $realPath = strtolower(realpath($path));
        if (!is_dir($realPath) && is_file($realPath)) {
            $realPath = preg_replace('/[\\\\\\/]+$/i', '', str_replace(basename($realPath), '', $realPath));
        }
        
        if (!$forceRemove) {
            foreach (self::safeRemovePath() as $sp => $a) {
                if (strpos($realPath, strtolower($sp)) !== false) {
                    $canDelete = true;
                    break;
                }
            }
            if (!$canDelete) {
                return;
            }
        }
        if (is_file($path)) {
            try {
                unlink($path);
            } catch (Exception $ex) {

            }
        }
        else if (is_dir($path)) {
            $files = (new linq(self::getFiles($path, false, true, true)))->select(function($name) use ($path){
                return $path . DIRECTORY_SEPARATOR .  substr($name, 2);
            })->getData();
            
            (new linq($files))->for_each(function($p) use ($forceRemove){
                FileSystem::Remove($p, $forceRemove);
            });
            rmdir($path);
        }
    }
    
}
?>