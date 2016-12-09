<?php

namespace Solcloud\Utils;

class ArrayValidatorObject
{

    /** @var string */
    public $value;

    /** @var string */
    public $error;

    public function __construct($value, $error = '')
    {
        $this->value = trim($value);
        $this->error = $error;
    }

}
