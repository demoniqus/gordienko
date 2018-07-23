<?php

class typesProcessor {
    public static function getGlobalTypeByValue($val) {
        return self::getGlobalTypeByTypeName(gettype($val));
    }
    
    public static function getGlobalTypeByTypeName($typeName) {
        $type = null;
        switch (strtolower($typeName))
        {
            case "decimal":
            case "int":
            case "integer":
            case "int32":
            case "int64":
            case "long":
            case "int16":
            case "float":
            case "double":
            case "byte":
            case "sbyte":
            case "short":
            case "ushort":
            case "ulong":
                $type = "number";
                break;
            case "boolean":
            case "bool":
            case "bit":
                $type = "bool";
                break;
            default:
                $type = "string";
                break;
        }
        return $type;
    }
}