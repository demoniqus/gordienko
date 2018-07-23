
<?php

$db = new DataBase(
    GlobalVars::$host, 
    GlobalVars::$dbName, 
    GlobalVars::$hostUser, 
    GlobalVars::$hostPass
);

/*Получим информацию из БД о лоте*/
$key = 'IdLot';
$IdLot = preg_match('/^\d+$/', $_REQUEST[$key]) ? $_REQUEST[$key] : '-1';

$lot = $db->lot_list->getEntity($IdLot);


if ($lot !== null) {
    
    echo '<script type="text/javascript">lot = ' . json_encode($lot) . ';</script>';
    echo '<script type="text/javascript">realURL = \'https://www.copart.com/lot/' . $lot['Key'] . '\';</script>';
    echo '<script type="text/javascript">lot_html_data = \'' . base64_encode(file_get_contents('https://www.copart.com/lot/' . $lot['Key'])) . '\';</script>';
    echo '<script type="text/javascript">pdf_loader.run();</script>';
    
}






?>