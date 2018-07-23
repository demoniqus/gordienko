<?php
/*
 * Класс для работы с файловой системой
 */
class FileSystem {
    
    public static function getPath($pathParts) {
        return join(DIRECTORY_SEPARATOR, $pathParts);
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
//                    unlink($fileName);
                }
            });
        }
    }
    
    public static function getFiles($rootDir, $useBaseDir = false, $mask = null) {
        return self::_getFiles($rootDir, $useBaseDir, 0, $mask);
    }
    
    private static function _getFiles($rootDir, $useBaseDir = false, $deep = 0, $mask = null) {
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
            function($el) use ($rootDir, &$files, &$directories, $templates, $realRootPath){
                if ($el === '.' || $el === '..') {
                    return;
                }
                $path = $rootDir . $el;
                if (is_file($path)) {
                    /*Если получен файл, запоминаем его, делая его путь относительным*/
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
                else  if (is_dir($path)) {
                    /*Если получена директория, ее будем сканировать тоже*/
                    $directories[] = $path;
                }
            }
        );
        /*Спускаемся рекурсивно*/
        (new linq($directories))->for_each(function($dirName) use (&$files, $mask, $deep, $useBaseDir){
            (new linq(self::_getFiles($dirName, $useBaseDir, $deep + 1, $mask)))
                ->for_each(function($fileName) use (&$files, $dirName){
                    $fileName = basename($dirName) . DIRECTORY_SEPARATOR . $fileName;
                    $files[] = $fileName;
                });
        });
        if ($deep === 0) {
            $pathPrefix = '.' . DIRECTORY_SEPARATOR . ($useBaseDir ? basename($rootDir) . DIRECTORY_SEPARATOR : '');
            
            foreach($files as $k => $v) {
                $files[$k] = $pathPrefix . $v;
            }
        }
        return $files;
        
    }
}
?>