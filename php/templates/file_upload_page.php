<script type="text/javascript">
    window.top.lot.setUploadedFiles(
<?php

/*
 * Работу с архивами внедрим позже
 */
$res = array();
$key = 'uploaded_files';
if (array_key_exists($key, $_FILES)) {
    !array_key_exists($key, $_SESSION) && ($_SESSION[$key] = array());
    $files = $_FILES[$key];
    $index = 0;
    $extractArchives = false;
    if (array_key_exists('archive_extract', $_REQUEST)) {
        $extractArchives = !!$_REQUEST['archive_extract'];
    }
    while (true) {
        if (array_key_exists($index, $files['name'])) {
            $origFileName = $files['name'][$index];
            $sourceFileName = $files['tmp_name'][$index];
            $targetDirectory = '.' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . session_id() . DIRECTORY_SEPARATOR;
            $targetFile = $targetDirectory . $origFileName;
            FileSystem::copyFile($sourceFileName, $targetFile);
            if ($extractArchives) {
                $pathInfo = pathinfo(realpath($targetFile));
                $fExt = array_key_exists('extension', $pathInfo) ? strtolower($pathInfo['extension']) : '';
                
            }
            $res[] = array(
                'OrigName' => $origFileName,
                'FileName' => $targetFile,
                'Key' => FileSystem::getUniqueKey(),
                'SourceFile' => $sourceFileName
            );
            /*
             * Регистрируем в сессии, чтобы не полагаться на полученное от пользователя 
             * наименование файла, которое следует запомнить, а в будущем показать ему,
             * иначе он может получить доступ к системным файлам
             */
            $_SESSION[$key][$res[count($res) - 1]['Key']] = $res[count($res) - 1];
            
        }
        else {
            break;
        }
        $index++;
    }
}

echo json_encode($res);
?>
    );
</script>