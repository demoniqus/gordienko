<?php
/*Класс для работы с массивами данных*/
class linq {
    private $data = null;
    private $mode = 'array';
    public function __construct($data, $mode = 'array') {
        if (gettype($data) === 'object') {
            $tmp = array();
            foreach ($data as $k => $v) {
                $tmp[$k] = $v;
            }
            $data = $tmp;
            $mode = 'assoc';
        }
        if (gettype($data) !== gettype(array())) {
            throw new Exception('Linq может работать только с объектами типа Array');
        }
        $this->data = $data;
        $this->mode = strtolower($mode ? $mode . '' : 'array') !== 'assoc' ? 'array' : 'assoc';
    }
    public function first($callback = null) {
        $el = null;
        if (gettype($callback) === gettype(function(){})) {
            foreach ($this->data as $k => $v) {
                if ($callback($v, $k) === true) {
                    $el = $v;
                    break;
                }
            }
        }
        else {
            foreach ($this->data as $v) {
                $el = $v;
                break;
            }
        }
        return $el;
    }
    public function firstKey($callback = null) {
        $el = null;
        if (gettype($callback) === gettype(function(){})) {
            foreach ($this->data as $k => $v) {
                if ($callback($v, $k) === true) {
                    $el = $k;
                    break;
                }
            }
        }
        else {
            foreach ($this->data as $k => $v) {
                $el = $k;
                break;
            }
        }
        return $el;
    }
    public function reduce($callback, $startVal){//f(очередной "сжатый" результат, элемент коллекции, ключ, коллекция)
        $res = $startVal;
        if ($this->mode !== 'array') {
            foreach($this->data as $k => $v) {
                $res = $callback($res, $v, $k, $this->data);
            }
        }
        else {
            for ($i = 0; $i < count($this->data); ++$i) {
                $res = $callback($res, $this->data[$i], $i, $this->data);
            }
            
        }
        /*Результат может иметь любой тип значения, в т.ч. и не быть какой-либо коллекцией*/
        return $res;
    }
    public function where($callback) {
        $conte = array();
        switch ($this->mode) {
            case 'assoc':
                foreach ($this->data as $k => $v) {
                    if($callback($v, $k) === true) {
                        $conte[$k] = $v;
                    }
                }
                break;
            case 'array':
                foreach ($this->data as $k => $v) {
                    if($callback($v, $k) === true) {
                        $conte[] = $v;
                    }
                }
                break;
        }
        $this->data = $conte;
        return $this;
    }
    public function valuesToArray() {
        $conte = array();
        foreach ($this->data as $v) {
            $conte[] = $v;
        }
        $this->data = $conte;
        return $this;
    }
    public function keysToArray () {
        $conte = array();
        foreach ($this->data as $k => $v) {
            $conte[] = $k;
        }
        $this->data = $conte;
        return $this;
    }
    public function select($callback) {
        foreach ($this->data as $k => &$v) {
            $v = $callback($v, $k);
        }
        return $this;
    }
    public function for_each ($callback) {
        foreach ($this->data as $k => &$v) {
            $callback($v, $k);
        }
        return $this;
    }
    public function toAssoc ($callback, $callback2 = null) {
        $conte = array();
        foreach ($this->data as $k => $v) {
            $conte[$callback($v, $k)] = $callback2 ? $callback2($v, $k) : $v;
        }
        $this->data = $conte;
        return $this;
        
    }
    public function groupBy($callback) {
        $groupKey = null;
        $res = [];
        $f = function($el) use ($callback) {
            $groupKey = $callback($el);
            if ($groupKey === null) {
                return;
            }
            !array_key_exists($groupKey, $res) && ($res[$groupKey] = array());
            $res[$groupKey][] = $el;
        };
        foreach ($this->data as $v) {
            $f($v);
        }
        return $this;
    }
    public function getData() {
        return $this->data;
    }
}
?>