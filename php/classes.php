<?php

class StandPrototype  {
    protected $fields = array();
    protected $entityName = null;
    protected $db = null;
    protected $extLinks = null;
    
    public static $regexp = array(
        'safeSQLValue' => '/[\'\\`*\\/\\\\]+/i'
    );
    
    /*
     * Список полей, значения которых в БД хрнаятся в виде base64-кодированной строки
     */
    protected $base64Keys = array();
    
    /*
     * Якоря - если существует хоть одна внешняя связь с объектом типа, указанного
     * в данном массиве, то текущий объект не может быть удален.
     * Типы указываются в нижнем регистре.
     * Это используется для того, чтобы сохранять целостность данных: например,
     * если с лотом связаны финансовые данные, то такой лот нельзя удалять из БД.
     */
    protected $anchors = array(
        /*'table_name' => 'table_name'*/
    );
    
    public static function CreateInstance($fields, $entityName) {
        if (class_exists($entityName)) {
            return new $entityName($fields, $entityName);
        }
        else {
            return new StandPrototype($fields, $entityName);
        }
        
    }
    
    protected static function clearValueForSQL($val) {
        return preg_replace(self::$regexp['safeSQLValue'], '', $val);
    }


    /*
     * $fields - массив полей нового объекта
     * $entityName - наименование объекта (наименование таблицы БД, реализующей объект).
     *      Регистр имеет значение.
     */
    public function __construct($fields, $entityName) {
        $this->fields = $fields;
        $this->entityName = $entityName;
    }
    
    public function CanDelete($db = null) {
        /*
         * Чтобы не создавать множество новых экземпляров подключения к БД, 
         * будем по возможности передавать одно подключение всем связанным объектам - 
         * это поможет сэкономить время
         */
        $this->_setDBConnection($db);
        $_self = $this;
        /*Получим все внешние объекты, связанные с текущим, если данная операция еще не была произведена*/
        $this->extLinks === null && ($this->extLinks = $this->getExternalLinks($_self->db));
        return (new linq($this->extLinks))->first(function($extLink) use ( $_self) { 
            return array_key_exists(strtolower($extLink->ObjType()), $_self->anchors) || $extLink->CanDelete($_self->db) === FALSE; 
        }) !== null ? FALSE : TRUE;
    }
    
    public function Delete($db = null) {
        /*
         * Чтобы не создавать множество новых экземпляров подключения к БД, 
         * будем по возможности передавать одно подключение всем связанным объектам - 
         * это поможет сэкономить время
         */
        $this->_setDBConnection($db);
        if ($this->CanDelete() !== false) {
            $entityName = $this->entityName;
            $this->db->$entityName->Delete($this->fields);
            $_self = $this;
            (new linq($this->extLinks))->for_each(function($extLink) use ($_self){
                $extLink->Delete($_self->db);
            });
            return true;
        }
        return false;
    }
    
    protected function _setDBConnection($db = null) {
        !$this->db && ($this->db = ($db ? $db : new DataBase(
            GlobalVars::$host, 
            GlobalVars::$dbName, 
            GlobalVars::$hostUser, 
            GlobalVars::$hostPass
        )));
        
    }

    public function Insert() {
        $this->_setDBConnection();
        
        if (($fields = $this->_beforeInsert($this->fields)) !== false) {
            $entName = $this->entityName;
            $this->fields = $this->_base64Decode($this->db->$entName->Insert($this->_base64Encode($fields)));
            return true;
        }
        return false;
        
    }
    
    /*
     * Метод позволяет в зависимости от конкретного типа 
     * обработать поля или остановить дальнейшее выполнение 
     * процедуры, если обнаружены существенные ошибки в данных
     */
    protected function _beforeInsert($fields) {
        return $fields;
    }
    
    public function Update() {
        $this->_setDBConnection();
        if (($fields = $this->_beforeUpdate($this->fields)) !== false) {
            $entName = $this->entityName;
            $this->fields = $this->_base64Decode($this->db->$entName->Update($this->_base64Encode($fields)));
            return true;
        }
        return false;
    }
    
    /*
     * Метод позволяет в зависимости от конкретного типа 
     * обработать поля или остановить дальнейшее выполнение 
     * процедуры, если обнаружены существенные ошибки в данных
     */
    protected function _beforeUpdate($fields) {
        return $fields;
    }
    
    
    public function ObjType () {
        return $this->entityName;
    }
    
    public function getFields() {
        return $this->fields;
    }
    
    public function getExternalLinks($db = null) {
        $_self = $this;
        $res = array();
        !$this->db && ($this->db = ($db ? $db : new DataBase(
            GlobalVars::$host, 
            GlobalVars::$dbName, 
            GlobalVars::$hostUser, 
            GlobalVars::$hostPass
        )));
        $query = 'SELECT `REFERENCED_COLUMN_NAME`,`TABLE_NAME`,`COLUMN_NAME` FROM '
            . 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE `REFERENCED_TABLE_SCHEMA`=\'' . GlobalVars::$dbName . '\' '
            . 'and `REFERENCED_TABLE_NAME`=\'' . $this->entityName . '\'';
        (new linq($this->db->query($query)))
            ->where(function($row){ return $row !== null && count($row) > 0; })
            ->for_each(function($row) use ($_self, &$res){
                $extLinkType = $row['TABLE_NAME'];
                $key = $row['REFERENCED_COLUMN_NAME'];
                $extLinks = $_self->db->$extLinkType->getObjects('`' . $row['COLUMN_NAME'] . '`=' . $_self->$key);
                $i = 0;
                $c = count($extLinks);
                while($i < $c) {
                    $res[] = $extLinks[$i++];
                }
            });
        return $res;
    }
    
    protected function _base64Encode ($fields) {
        foreach ($this->base64Keys as $key) {
            if (array_key_exists($key, $fields)) {
                $fields[$key] && ($fields[$key] = base64_encode($fields[$key]));
            }
        }
        return $fields;
    }
    protected function _base64Decode ($fields) {
        foreach ($this->base64Keys as $key) {
            if (array_key_exists($key, $fields)) {
                $fields[$key] && ($fields[$key] = base64_decode($fields[$key]));
            }
        }
        return $fields;
    }
    
    public function Base64EncodeFields() {
        $this->fields = $this->_base64Encode($this->fields);
    }
    
    public function Base64DecodeFields() {
        $this->fields = $this->_base64Decode($this->fields);
    }
    
    public function __get($name) {
        return array_key_exists($name, $this->fields) ? $this->fields[$name] : null;
    }
    public function __set($name, $value) {
        $this->fields[$name] = $value;
    }
    
    
    
}


class lot_params_values extends StandPrototype {
    public function __construct($fields, $entityName = null) {
        $this->fields = $fields;
        $this->entityName = get_called_class();
    }
    /*
     * Данный тип не требует специальных условий для удаления
     */
//    public function Delete($db = null) {
//        parent::Delete($db);
//    }
    protected function _beforeUpdate($fields) {
        /*Эти ключи нельзя обновлять в уже существующем значении параметра*/
        $deniedKeys = array ('IdParam', 'IdLot');
        foreach ($deniedKeys as $key) {
            if (array_key_exists($key, $fields)) {
                unset($fields[$key]);
            }
        }
        $key = 'Value';
        if (!array_key_exists($key, $fields) || $fields[$key] === null || str_replace(' ', '', $fields[$key]) === '') {
            /*Пустое значение параметра сохранять не будем, а удалим его из БД*/
            $this->Delete();
            return false;
        }
        return $fields;
    }
    
    protected function _beforeInsert($fields) {
        $key = 'IdParam';
        if (!array_key_exists($key, $fields) || $fields[$key] < 1) {
            /*
             * Параметр должен принадлежать какому-либо лоту и быть связан с auction_params
             */
            return false;
        }
        $key = 'IdLot';
        if (!array_key_exists($key, $fields) || $fields[$key] < 1) {
            /*
             * Параметр должен принадлежать какому-либо лоту и быть связан с auction_params
             */
            return false;
        }
        $key = 'Value';
        if (!array_key_exists($key, $fields) || $fields[$key] === null || str_replace(' ', '', $fields[$key]) === '') {
            /*
             * Пустое значение вставлять не нужно
             */
            return false;
        }
        
        return $fields;
    }
}

class lot_images extends StandPrototype {
    
    protected $base64Keys = array ('OrigName', 'FileName');
    
    public function __construct($fields, $entityName = null) {
        $this->fields = $fields;
        $this->entityName = get_called_class();
    }
    
    public function Delete($db = null) {
        if ($this->fields['FileName']) {
            $fileName = GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . base64_decode($this->fields['FileName']);
            FileSystem::Remove($fileName);
        }
        return parent::Delete($db);
    }
    
    protected function _beforeUpdate($fields) {
        /*Эти ключи нельзя обновлять в уже существующем значении параметра*/
        $deniedKeys = array ('IdLot', 'OrigName', 'FileName', 'Width', 'Height');
        foreach ($deniedKeys as $key) {
            if (array_key_exists($key, $fields)) {
                unset($fields[$key]);
            }
        }
        return $fields;
    }
    
    protected function _beforeInsert($fields) {
        $key = 'IdLot';
        if (!array_key_exists($key, $fields) || $fields[$key] < 1) {
            /*
             * Изображение должно принадлежать какому-либо лоту 
             */
            return false;
        }
        return $fields;
    }
}

class lot_list extends StandPrototype {
    protected $base64Keys = array ('DataFolder', 'BaseURL', 'Maker', 'Model');
    
    public function __construct($fields, $entityName = null) {
        $this->fields = $fields;
        $this->entityName = get_called_class();
    }
    
    
    
    public function Delete($db = null) {
        if ($this->fields['DataFolder']) {
            $dirName = GlobalVars::$lotDataDir . DIRECTORY_SEPARATOR . base64_decode($this->fields['DataFolder']);
            FileSystem::Remove($dirName);
        }
        return parent::Delete($db);
    }
    
    protected function _beforeUpdate($fields) {
        /*Эти ключи нельзя обновлять в уже существующем лоте*/
        $deniedKeys = array ('Key', 'IdAuction', 'DataFolder');
        foreach ($deniedKeys as $key) {
            if (array_key_exists($key, $fields)) {
                unset($fields[$key]);
            }
        }
        return $fields;
    }
    
    protected function _beforeInsert($fields) {
        $key = 'IdAuction';
        if (!array_key_exists($key, $fields) || $fields[$key] < 1) {
            /*
             * Лот должен принадлежать какому-либо аукциону
             */
            return false;
        }
        $key = 'Key';
        if (!array_key_exists($key, $fields) || !preg_replace('/[^0-9a-zа-я]+/i', '', $fields[$key])) {
            /*
             * Ключ должен быть осмысленным
             */
            return false;
        }
        /*Т.к. Key хранится в открытом виде, то определенные символы в нем запретим*/
        $fields[$key] = preg_replace(self::$regexp['safeSQLValue'], '', $fields[$key]);
        $entName = $this->entityName;
        if (count($this->db->$entName->getRows('`Key`=\'' . preg_replace(self::$regexp['safeSQLValue'], '', $fields[$key]) . '\'')) > 0) {
            /*
             * Ключ должен быть уникальным
             */
            return false;
        }
        
        return $fields;
    }
    
    public function getImages($onlyVisible = false) {
        $this->_setDBConnection();
        return $this->db->lot_images->getRows('`IdLot`=' . $this->fields['IdLot'] . ($onlyVisible ? ' AND `Visible`=1' : ''));
    }
    
    public function getParams($onlyVisible = false) {
        $this->_setDBConnection();
        return ($this->db->query(
            'select ap.*, lpv.IdParamValue, lpv.Value, lpv.IdLot from (select * from lot_params_values where `IdLot`=' 
            . $this->fields['IdLot'] . ') as lpv left ' . 
            ' join (select * from auction_params where `IdAuction`=' . $this->fields['IdAuction'] 
            . ') as ap on ap.IdParam=lpv.IdParam where ap.IdParam IS NOT NULL ' . ($onlyVisible ? ' AND ap.Visible=1' : '') . ' order by ap.OrderNum', 
            true
        ));
    }
    public function getSaleDate($format) {
        $res = '';
        if ($this->SaleDate) {
            $dt = $this->SaleDate;
            if (gettype($this->SaleDate) == gettype('aaa')) {
                $dt = new DateTime($this->SaleDate);
            }
            $res = $dt->format($format);
        }
        return $res;
    }
    
    
    
}