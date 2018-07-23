<link href="css/calendar.css" type="text/css" rel="stylesheet"/>
<link href="css/filter.css" type="text/css" rel="stylesheet"/>
<script type="text/javascript" src="js/calendar.js"></script>
<script type="text/javascript" src="js/filter.js"></script>
<script type="text/javascript" src="js/nav-panel.js"></script>
<script type="text/javascript" src="js/base_class.js"></script>
<script type="text/javascript" src="js/auction.js"></script>
<script type="text/javascript" src="js/lot_image.js"></script>
<script type="text/javascript" src="js/lot.js"></script>
<script type="text/javascript" src="js/auct_lot_param.js"></script>
<div class="nav-panel">
    <ul class="main-nav-menu">
        <li key="auctiones_list" base_class="auction" >Аукционы</li>
        <li key="lots_list" base_class="lot" >Лоты</li>
    </ul>
    
</div>
<div class="data-panel">
    
</div>
<?php
/*
 * При запросе главной страницы также будем вызывать очистку временного каталога.
 * В этом случае данная операция будет выполняться достаточно, но не слишком часто.
 */
$boundDate = GlobalVars::tempFilesMaxDate();

(new linq(FileSystem::getFiles('.' . DIRECTORY_SEPARATOR . GlobalVars::$tmpDirName)))->for_each(function($fileName) use ($boundDate){
    $filePath = '.' . DIRECTORY_SEPARATOR . GlobalVars::$tmpDirName . substr($fileName, 1);
    if (stat($filePath)['mtime'] > $boundDate) {
        return;
    }
    
    FileSystem::Remove($filePath);
});
?>
