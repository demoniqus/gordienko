<?php

$rootDir = '.' . DIRECTORY_SEPARATOR . 'tmp';
$files = FileSystem::getFiles($rootDir, true, true, false);
(new linq($files))->for_each(function($path){
    /*Файлы старше двух суток удаляем*/
    if (time() - filemtime($path) > 2 * 60 * 60 * 24) {
        echo $path . ' => ' . filemtime($path) . ' => ' . date('H:i:s d-m-Y' ,filemtime($path)) . "\n";
        FileSystem::Remove($path);
    }
});
echo '{"success":true}';

?>