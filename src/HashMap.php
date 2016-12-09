<?php

namespace Solcloud\Utils;

use InvalidArgumentException;

class HashMap {

    protected static $data = [];

    public static function has($key) {
        if (array_key_exists($key, static::$data)) {
            return true;
        }

        return false;
    }

    public static function get($key, $returnIfNotExists = NULL, $throwExeptionWhenDefaultNull = true) {
        if (static::has($key)) {
            return static::$data[$key];
        }

        if ($returnIfNotExists === NULL && $throwExeptionWhenDefaultNull) {
            throw new InvalidArgumentException("Key '{$key}' do not exists");
        }

        return $returnIfNotExists;
    }

    public static function set($key, $value) {
        static::$data[$key] = $value;
    }

    public static function getMap() {
        return static::$data;
    }

}
