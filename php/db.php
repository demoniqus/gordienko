<?php
/*
 * Данный класс выполняет работу с базой данных
 */
class DataBase {
    /*Свойство определяет, какая версия команд используется для работы с БД*/
    private $dbtype;
    /*Текущее подключение к БД*/
    private $connection;
    /*Имя используемой базы данных*/
    private $dbname;
    /*
     * Наименование текущего объекта, который следует загрузить из БД и 
     * характеристики его полей.
     */
    private $currentObject;
    
    function __construct(
            /*все параметтры - строки*/
            $host, $dbName, $login, $pass
            ) {
        /*
         * Проверим, с какой версией языка идет работа и какой набор команд использовать
         * для дальнейшей работы
         */
        switch (true) {
            case function_exists('mysqli_connect'):
                $this->dbtype = 'mysqli';
                break;
            case function_exists('mysql_connect'):
                $this->dbtype = 'mysql';
                break;
            default:
                throw new Exception('Не удалось определить способ подключения к БД. Обратитесь к разработчику.');
        }
        $this->connect($host, $dbName, $login, $pass);
    }
    
    //Метод непосредственно выполняет соединение с требуемой БД
    private function connect(
            /*все параметтры - строки*/
            $host, $dbName, $login, $pass
            ) {
        $host = $host == '' ? 'localhost' : $host;
        $login = $login == '' ? 'root' : $login;
        $pass = $pass == '' ? '' : $pass;//null, 0 тоже превращаем в пустую строку
        try {
            $command = $this->dbtype . '_connect';
            $this->connection = $command($host, $login, $pass);
            
        } catch (Exception $ex) {
            throw new Exception('Не удалось подключиться к БД с текущими параметрами авторизации');
        }
        if ($this->dbtype === 'mysqli') {
            $command = 'mysqli_select_db';
            if (!$command($this->connection, $dbName)) {
                throw new Exception('Не удалось подключиться к БД ' + $dbName);
            }
        }
        else {
            $command = 'mysql_select_db';
            if (!$command($dbName, $this->connection)) {
                throw new Exception('Не удалось подключиться к БД ' + $dbName);
            }
        }
        /*После подключения к БД запомним ее имя*/
        $this->dbname = $dbName;
    }
    
    //Метод позволяет выполнить запрос к БД
    public function query(
            $query, //строка
            $mode = 'assoc') {
        $data = array();
        if ($query) {
            /*Получаем результат запроса из БД*/
            $result = $this->_execQueryCommad($query);
            /*Определим количество записей*/
            $command = $this->dbtype . '_num_rows';
            if ($result != false) {
                $rowsCount = $command($result);
                /*Получим наименование команды для обработки очередной записи*/
                $mode = $mode && strtolower((string)$mode) !== 'array' ? 'assoc' : 'array';
                $command = $this->dbtype . '_fetch_' . $mode;
                /*Собираем массив записей, полученных из БД*/
                while($rowsCount-- > -1) {
                    $data[] = $command($result);
                }
            }
        }
        return $data;
    }
    
    protected function _execQueryCommad($query) {
        $result = null;
        $command = $this->dbtype . '_query';
        /*Получаем результат запроса из БД*/
        switch ($this->dbtype) {
            case 'mysqli':
                $result = $command($this->connection, $query);
                break;
            case 'mysqli':
                $result = $command($query, $this->connection);
                break;
        }
        return $result;
    }


    /*Метод позволяет выбирать строки БД с правильной типизацией полей*/
    public function getRows(
            $condition = null, //строка
            $required_fields = null //массив наименований колонок для выборки
            ) {
        if (!$this->currentObject) {
            throw new Exception('Не установлен объект для извелечения из БД');
        }
        /*Определим список полей, которые требуется извлечь*/
        (
            !$required_fields || 
            (gettype($required_fields) !== gettype(array())) || 
            count($required_fields) < 1
        ) &&
        $required_fields = array('*');//По умолчанию извлекаются все поля
        /*Отберем список колонок, которым надо преобразовать тип из строкового*/
        $columns = $this->currentObject['fields'];
        /*Запросим строки и сразу произведем типизацию*/
        $query = 'select ' . implode(', ', $required_fields) . ' from ' . $this->currentObject['name'] . ' ' . ($condition ? 'where ' . $condition : '');
        $rows = (new linq($this->query($query)))
            ->where(function($row){ return count($row) > 0;})
            ->for_each(function(&$row) use ($columns){
                self::_convertValue($row, $this->currentObject['fields']);
            })->getData();
        return $rows;
        
    }
    
    /*Метод удаляет запись из БД*/
    public function Delete(
            $entity
            ) {
        if (!$this->currentObject) {
            throw new Exception('Не установлен объект для удаления из БД');
        }
        if ($entity === null || gettype($entity) !== gettype($entity) || count($entity) < 1) {
            throw new Exception('Нет информации для удаления из БД');
        }
        /*Проверим наличие первичного ключа в таблице - без него удаление этим методом невозможно*/
        if (($pk = (new linq($this->currentObject['fields'], 'assoc'))
        ->first(function($column){ return $column['_primary_key'];})) === null) {
            throw new Exception('Данная таблица не имеет первичного ключа. Поэтому удаление данным методом невозможно');
        }
        if (!array_key_exists($pk['column_name'], $entity)) {
            throw new Exception('Полученный объект не содержит информации о первичном ключе. Удаление невозможно.');
        }
        /*Создаем запрос для удаления записи*/
        $query = 'DELETE FROM ' . $this->currentObject['name'] . ' WHERE  ' . $pk['column_name'] . '=' . $entity[$pk['column_name']] . ' LIMIT 1';
                        
        return $this->_execQueryCommad($query);
    }
    
    /*Метод добавляет запись в БД*/
    public function Insert(
            $entity
            ) {
        if (!$this->currentObject) {
            throw new Exception('Не установлен объект для извелечения из БД');
        }
        if ($entity === null || gettype($entity) !== gettype($entity) || count($entity) < 1) {
            throw new Exception('Нет информации для вставки в БД');
        }
        $columns = $this->currentObject['fields'];
        /*Создаем запрос для вставки записи*/
        $query = 'INSERT INTO ' . $this->currentObject['name'] . ' (' .
                join(
                    ',',
                    (new linq($this->currentObject['fields'], 'assoc'))
                    ->where(function($column){ return $column['_primary_key'] ? false : true;})
                    ->select(function($column){return '`' . $column['column_name'] . '`';})
                    ->getData()
                )
                .') VALUES(' .
                join(
                    ',',
                    //(new linq($entity, 'assoc'))
                    (new linq($this->currentObject['fields'], 'assoc'))
                        ->where(function($column) {return $column['_primary_key'] ? false : true;})
                        ->select(function($column) use ($entity) {
                            $colKey = $column['column_name'];
                            return self::_getValueForQuery(
                                array_key_exists($colKey, $entity) ? $entity[$colKey] : NULL,
                                $column
                            );
                        })
                    ->getData()
                )
                .')';
                        
        $this->_execQueryCommad($query);
        /*После вставки попробуем вернуть вставленную запись, если данная таблица имеет первичный ключ*/
        $result = null;
        if ((new linq($this->currentObject['fields'], 'assoc'))
        ->first(function($column){ return $column['_primary_key'];})) {
            $command = $this->dbtype . '_insert_id';
            $lastId = $command($this->connection);
            $table_name = $this->currentObject['name'];
            $result = $this->$table_name->getEntity($lastId);
        }
        return $result;
    }
    
    /*Метод обновляет запись в БД*/
    public function Update(
            $entity
            ) {
        if (!$this->currentObject) {
            throw new Exception('Не установлен объект для извелечения из БД');
        }
        if ($entity === null || gettype($entity) !== gettype($entity) || count($entity) < 1) {
            throw new Exception('Нет информации для вставки в БД');
        }
        /*Проверим наличие первичного ключа в таблице - без него обновление этим методом невозможно*/
        if (($pk = (new linq($this->currentObject['fields'], 'assoc'))
        ->first(function($column){ return $column['_primary_key'];})) === null) {
            throw new Exception('Данная таблица не имеет первичного ключа. Поэтому обновление данным методом невозможно');
        }
        if (!array_key_exists($pk['column_name'], $entity)) {
            throw new Exception('Полученный объект не содержит информации о первичном ключе. Обновление невозможно.');
        }
        /*Составляем запрос на обновление*/
        $columns = $this->currentObject['fields'];
        $query = 'UPDATE ' . $this->currentObject['name'] . ' SET ' . 
            join(
                ',',
                (new linq($entity, 'assoc'))
                ->where(function($v, $k) use ($pk) {
                    return $k !== $pk['column_name'];
                })
                ->select(function($v, $k) use ($columns) {
                    return '`' . $k . '`=' . self::_getValueForQuery($v, $columns[$k]);
                })
                ->getData()
            ).
            ' WHERE ' . $pk['column_name'] . '=' . $entity[$pk['column_name']];
        
        $this->_execQueryCommad($query);
        /*Вернем обновленную информацию*/
        $table_name = $this->currentObject['name'];
        return $this->$table_name->getEntity($entity[$pk['column_name']]);
    }
    
    protected static function _getValueForQuery($v, &$column) {
        if ($v === null) {
            return 'null';
        }
        switch ($column['data_type']) {
            case 'int':
            case 'year':
            case 'bigint':
            case 'mediumint':
            case 'smallint':
            case 'tinyint':
            case 'decimal':
            case 'dec':
            case 'double':
            case 'float':
            case 'real':
                /*
                 * Защита от ввода недопустимых значений,
                 * которые потенциально опасны для БД
                 */
                if (!is_numeric($v)) {
                    $v = 0;
                }
                return $v;
            case 'char':
            case 'varchar':
            case 'nvarchar':
            case 'text':
            case 'tinytext':
            case 'mediumtext':
                /*В строковых значениях необходимо экранировать кавычки и обратные слеши*/
                return $v != null ? '\'' . str_replace('\'', '\'\'', str_replace('\\', '\\\\', (string)$v)) . '\'' : 'null';
            case 'bit':
                return gettype($v) === gettype(true) ?
                    ($v ? '1' : '0') :
                    ((($v = strtolower($v)) && $v === 'true' || $v === '1') ? '1' : '0');
            case 'json':
                if (gettype($v) === gettype('') ) {
                    /*Это строка, которую надо распарсить перед сохранением*/
                    if ($v !== '') {
                        $v = json_decode($v);
                    }
                }
                else {
                    /*Это готовый объект для сохранения*/
                        $v = $v;
                }
                return $v;
            case 'datetime':
                $formatString = 'Y-m-d H:i:s';
                if (gettype($v) === gettype('') ) {
                    if ($v !== '') {
                        $v = new DateTime($v);
                        $v = '\'' . date($formatString, $v->getTimestamp()) . '\'';
                    }
                }
                else {
                    
                    try {
                        $v = '\'' . date($formatString, $v->getTimestamp()) . '\'';
                    } catch (Exception $ex) {
                        $v = 'null';
                    }
                }
                return $v;
            case 'date':
                $formatString = 'Y-m-d';
                if (gettype($v) === gettype('') ) {
                    if ($v !== '') {
                        $v = new DateTime($v);
                        $v = '\'' . date($formatString, $v->getTimestamp()) . '\'';
                    }
                }
                else {
                    
                    try {
                        $v = '\'' . date($formatString, $v->getTimestamp()) . '\'';
                    } catch (Exception $ex) {
                        $v = 'null';
                    }
                }
                return $v;
        }
    }
    
    public function getFirstRow(
            $condition = null, //строка
            $required_fields = null //массив строк
            ) {
        $rows = $this->getRows($condition, $required_fields);
        return $rows != null  && count($rows > 0) ? $rows[0] : null;
    }
    
    //Метод позволяет выбрать из БД конкретную запись по ее Id
    public function getEntity(            
            $IdEntity//Идентификатор записи
            ) {
        if (!$this->currentObject) {
            throw new Exception('Не установлен объект для извелечения из БД');
        }
        /*Найдем ключевую колонку*/
        $primaryKey = (new linq($this->currentObject['fields'], 'assoc'))->first(function($col){ return $col['_primary_key'] === true;});
        if ($primaryKey) {
            /*Отберем список колонок, которым надо преобразовать тип из строкового*/
            $columns = $this->currentObject['fields'];
            /*Запросим строки и сразу произведем типизацию*/
            $rows = (new linq($this->query('select * from ' . $this->currentObject['name'] . ' WHERE ' . $primaryKey['column_name'] . '=' . $IdEntity)))
                ->where(function($row){ return count($row) > 0;})
                ->for_each(function(&$row) use ($columns){
                    self::_convertValue($row, $this->currentObject['fields']);
                })->getData();
        }
        return count($rows) > 0 ? $rows[0] : null;
    }
    
    public function getEmptyEntity(
            $values = null
            ) {
        if (!$this->currentObject) {
            throw new Exception('Не установлен объект для извелечения из БД');
        }
        $values = gettype($values) === gettype(array()) ? $values : array();
        $entity = (new linq($this->currentObject['fields'], 'assoc'))
            ->toAssoc(
                function($column){ return $column['column_name'];},
                function($column) use ($values) { 
                    return array_key_exists($column['column_name'], $values) ? 
                        $values[$column['column_name']] : null;
                }
            )->getData();
        self::_convertValue($entity, $this->currentObject['fields']);
        return $entity;
        
    }
    
    protected static function _convertValue(&$entity, &$columns) {
        foreach ($entity as $k => &$v) {
            switch ($columns[$k]['data_type']) {
                case 'int':
                case 'year':
                case 'bigint':
                case 'mediumint':
                case 'smallint':
                case 'tinyint':
                    $entity[$k] = (int)$v;
                    break;
                case 'decimal':
                case 'dec':
                case 'double':
                case 'float':
                case 'real':
                    $entity[$k] = (float)$v;
                    break;
                case 'char':
                case 'varchar':
                case 'nvarchar':
                case 'text':
                case 'tinytext':
                case 'mediumtext':
                    $entity[$k] = $v . '';
                    break;
                case 'bit':
                    $entity[$k] = $v === '1' || $v === true || $v === 1;
                    break;
                case 'json':
                    if ($v !== null && trim($v) !== '') {
                        $entity[$k] = json_decode($v);
                    }
                    else {
                        $entity[$k] = null;
                    }
                    break;
                case 'datetime':
                case 'date':
                    $entity[$k] = new DateTime($v);
                    break;
            }
        }
    }


    public function __get($name/*Строка*/) {
        /*Получим список таблиц, имеющихся в БД*/
        $tablesList = $this->query('select table_name from information_schema.tables where table_schema=\'' . $this->dbname . '\'');
        /*Проверим, есть ли такая таблица в БД*/
        $name = strtolower($name);
        
        if (($table_params = (new linq($tablesList))->first(function($line) use ($name){ return strtolower($line['table_name']) === $name;})) === null) {
            throw new Exception('Неизвестный тип объекта.');
        }
        /*Получим необходимые характеристики, чтобы по ним построить выборку*/
        $this->currentObject = array(
            'name' => $name,
            'fields' => (new linq($this->query('select '
                    . 'table_name, '
                    . 'column_name, '
                    . 'data_type, '
                    . 'character_maximum_length as max_length, '
                    . 'numeric_precision as num_prec, '
                    . 'datetime_precision as dtime_prec, '
                    . 'character_set_name as char_set, '
                    . 'column_key, '
                    . 'is_nullable, '
                    . 'privileges '
                    . ' from information_schema.columns where table_name=\'' . $table_params['table_name'] . '\' and table_schema=\'' . $this->dbname . '\''))
                )->where(function($line){
                    return count($line) > 0;
                })->select(function($line){
                    $key = 'max_length';
                    $line[$key] !== null && ($line[$key] = (int)$line[$key]);
                    $key = 'num_prec';
                    $line[$key] !== null && ($line[$key] = (int)$line[$key]);
                    $key = 'column_key';
                    $line['_primary_key'] = $line[$key] !== null && strtolower($line[$key]) === 'pri';
                    $key = 'is_nullable';
                    $line[$key] = $line[$key] !== '1' ? false : true;
                    return $line;
                })->toAssoc(function($column){
                    return $column['column_name'];
                })->getData()
                    
        );
        return $this;
        
    }
}

function debug($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}
?>