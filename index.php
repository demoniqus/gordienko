<?php
session_start();
/*
 * 1) В автоматически генерируемых запросах на вставку, обновление, поиск сделать экранирование 
 * апострофов и обратных слешей для строковых значений
 * 2) Внедрить передачу больших объектов через ссылки без клонирования
 * 3) Проверить, как идет работа с адресами файлов, имеющих кириллицу
 * 4) Добавить маркер, что загрузка скриптов происходит через разрешенные точки входа
 */
    require_once '.' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'main_require.php';
    /*Определим, какой тип данных требуется клиенту*/
    $dataModeKey = GlobalVars::$dataModeKey;
    $requiredPageKey = GlobalVars::$requiredPageKey;
    if (!array_key_exists($dataModeKey, $_REQUEST)) {
        /*
         * Если в запросе не указаны режим загрузки и требуемая страница, значит 
         * запрошена индексная страница. Все остальные страницы должны быть защищены 
         * настройками сервера от прямого доступа. 
         * Данный файл index.php должен служить единой точкой входа в сервис.
         */
        $_REQUEST[$dataModeKey] = 'page';
        $_REQUEST[$requiredPageKey] = 'index';
    }
    $mode = strtolower(trim($_REQUEST[$dataModeKey] === null ? '' : $_REQUEST[$dataModeKey] . ''));

    switch ($mode) {
        /*
         * Наименования запрашиваемых файлов делаем составными, чтобы
         * пользователь не мог через данный механизм обратиться к произвольному файлу
         * и получить закрытую информацию
         */
        case 'data':
            $dataKeyKey = GlobalVars::$dataKeyKey;
            /*Получим наименование запрошенной страницы*/
            $page = strtolower(trim(!array_key_exists($dataKeyKey, $_REQUEST) || $_REQUEST[$dataKeyKey] === null ? '' : $_REQUEST[$dataKeyKey] . ''));
            if ($page) {
                $page = '.' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'data_sources' . DIRECTORY_SEPARATOR . $page . '_data.php';
                if(file_exists($page)) {
                    require_once $page;
                }
            }
            break;
        case 'page':
            /*Получим наименование запрошенной страницы*/
            $page = strtolower(trim($_REQUEST[$requiredPageKey] === null ? '' : $_REQUEST[$requiredPageKey] . ''));
            if ($page) {
                $page = '.' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $page . '_page.php';
                if(file_exists($page)) {
                    require_once '.' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
                    require_once $page;
                    require_once '.' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
                }
            }
            break;
        default:
            /*
             * Пользователь запрашивает непредусмотренный тип данных.
             * Блокируем такую возможность.
             */
            echo 'Получены не все параметры. Обратитесь к разработчику.';
            return;
    }
?>
