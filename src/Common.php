<?php

use CodeIgniter\Model;

if (! function_exists('get_foreign_key')) {
    /**
     * Get foreign key based on model info.
     */
    function get_foreign_key(Model $model): string
    {
        $refObj = new ReflectionObject($model);

        $refProp = $refObj->getProperty('table');
        $table   = $refProp->getValue($model);

        $refProp    = $refObj->getProperty('primaryKey');
        $primaryKey = $refProp->getValue($model);

        return singular($table) . '_' . $primaryKey;
    }
}

if (! function_exists('get_primary_key')) {
    /**
     * Get primary key based on model info.
     */
    function get_primary_key(Model $model): string
    {
        $refObj = new ReflectionObject($model);

        $refProp = $refObj->getProperty('primaryKey');

        return $refProp->getValue($model);
    }
}
