<?php

namespace Solcloud\Utils;

/**
 * Validation class for only one dimensional array of data
 */
class ArrayValidator
{

    /** @var bool */
    private $isLastRuleValid = TRUE;

    /** @var bool */
    private $isGroupValid = TRUE;

    /** @var ArrayValidatorObject[] */
    private $validatorObjs = [];

    /** @var string[] added by ->name() */
    private $validKeys = [];

    /** @var ArrayValidatorObject */
    private $currentObj;
    //default errors
    private $errorRequired;
    private $errorDate;
    private $errorEmail;
    private $errorUrl;
    private $errorMinSize;
    private $errorMaxSize;
    private $errorNumberFloat;
    private $errorNumberInteger;
    private $errorNumberMax;
    private $errorNumberMin;
    private $errorEqual;
    private $errorRegex;
    private $errorIn;
    private $errorSafeChars;
    private $errorSafePath;
    // internal regex
    private $patternEmail = '/^([a-zA-Z0-9_\+\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
    private $patternUrl = '@^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/\S*)?$/iuS@';
    private $patternNumberInteger = '/^[+-]?[0-9]+$/';
    private $patternNumberFloat = '/^[+-]?[0-9]+\.?[0-9]*$/';
    private $patternDate = '/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/';
    private $patternSafeChars = '/^[-_A-Z0-9]+$/i';
    private $patternSafePath = '@^[A-Z0-9_/-]+[^\/]\.[A-Z]{2,6}$@i';

    // public regex for use with regex() method
    const REGEX_URL = '@^https?://[^\r \n]+$@';

    /**
     * Construct for setting dataset array
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->translate();
        $this->init($data);
    }

    public function init(array $data)
    {
        $this->validatorObjs = [];
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $this->validatorObjs[$key] = new ArrayValidatorObject($value);
            }
        }
    }

    protected function translate()
    {
        $this->errorRequired = 'Tato položka je povinná';
        $this->errorDate = 'Prosím vložte datum ve formátu RRRR-MM-DD';
        $this->errorEmail = 'Prosím vložte platný email';
        $this->errorUrl = 'Prosím vložte platnou url adresu';
        $this->errorMinSize = 'Prosím vložte minimálně %s znaků';
        $this->errorMaxSize = 'Prosím vložte maximálně %s znaků';
        $this->errorNumberFloat = 'Pouze reálná čísla jsou povolená';
        $this->errorNumberInteger = 'Pouze celá čísla jsou povolená';
        $this->errorNumberMax = 'Vložte prosím hodnotu menší nebo rovno %s ';
        $this->errorNumberMin = 'Vložte prosím hodnotu větší nebo rovno %s ';
        $this->errorEqual = 'Položky se neshodují';
        $this->errorRegex = 'Prosím vložte hodnotu ve správném tvaru';
        $this->errorIn = 'Prosím vyberte hodnotu ze seznamu';
        $this->errorSafeChars = 'Povolené je podtržítko, celá čísla, znaky abecedy v rozmezí od A do Z (ale bez speciálních znaků a mezer)';
        $this->errorSafePath = 'Cesta obsahuje nepovolené znaky';
    }

    /**
     * Returns TRUE if last validation passed , otherwise FALSE
     * @return bool
     */
    public function isLastRuleValid()
    {
        return $this->isLastRuleValid;
    }

    /**
     * Returns TRUE if all validations passed , otherwise FALSE
     * @return bool
     */
    public function isGroupValid()
    {
        return $this->isGroupValid;
    }

    /**
     * Return's $name validation error
     * @param string $name field name
     * @return string error msg
     */
    public function getError($name)
    {
        if (isset($this->validatorObjs[$name])) {
            return $this->validatorObjs[$name]->error;
        }

        return '';
    }

    /**
     * Return associative array $name => errorMsg
     * @return array
     */
    public function getAllErrors()
    {
        $errors = [];
        foreach ($this->validKeys as $name) {
            $error = $this->getError($name);
            if ($error === '') {
                continue;
            }

            $errors[$name] = $error;
        }

        return $errors;
    }

    /**
     * Returs $name value if set otherwise empty string
     * @param string $name
     * @return string value or ''
     */
    public function getValue($name)
    {
        if (isset($this->validatorObjs[$name])) {
            return $this->validatorObjs[$name]->value;
        }

        return '';
    }

    /**
     * Return all values in associated array (key => value)
     * @param callable $sanitaze sanitaze function
     * @return array asociated array of all values
     */
    public function getValues($sanitaze = FALSE)
    {
        $output = array();
        if ($this->isGroupValid) {
            foreach ($this->validKeys as $key) {
                $value = $this->validatorObjs[$key]->value;

                if ($sanitaze) {
                    $output[$key] = $this->sanitaze($value, $sanitaze);
                } else {
                    $output[$key] = $value;
                }
            }
        }

        return $output;
    }

    /**
     * Used to set starting values on Form data
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->currentObj->value = (string) $value;

        return $this;
    }

    /**
     * Set current subject as invalid and set error msg
     * @param string $error
     * @return self
     */
    public function setError($error)
    {
        $this->currentObj->error = (string) $error;
        $this->isGroupValid = FALSE;
        $this->isLastRuleValid = FALSE;

        return $this;
    }

    /**
     * PRIVATE Helper to set error messages from validators
     * @param string $errorMsg custom error message
     * @param string $default  default error message
     * @param string $params   extra parameter to default error message
     */
    private function setErrorMsg($errorMsg, $default, $params = null)
    {
        $this->isGroupValid = FALSE;
        if ($errorMsg == null) {
            $this->currentObj->error = sprintf($default, $params);
        } else {
            $this->currentObj->error = $errorMsg;
        }
    }

    /**
     * Check conditions to decide if validation will be perform or not
     * @return bool
     */
    private function preCheck()
    {
        return ($this->isLastRuleValid && $this->currentObj->value !== '');
    }

    /* -------------------- */
    /* Validation Functions */
    /* -------------------- */

    /**
     * Used to set a pointer for current validation object
     * if $name doesnt exits, it will be created with a empty value ('')
     * note: validation always pass on empy not required fields
     * @param string $name as in array($name => 'name value')
     * @return self
     */
    public function name($name)
    {
        $this->validKeys[] = $name;
        if (!isset($this->validatorObjs[$name])) {
            $this->validatorObjs[$name] = new ArrayValidatorObject('');
        }

        $this->isLastRuleValid = TRUE;
        $this->currentObj = &$this->validatorObjs[$name];

        return $this;
    }

    /**
     * Note if field is required , then it must me called right after name!!
     * eg: $vf->name('phone')->required()->minSize(4);
     * @param string $errorMsg
     * @return self
     */
    public function required($errorMsg = NULL)
    {
        if ($this->isLastRuleValid) {
            $this->isLastRuleValid = ($this->currentObj->value !== '' ) ? TRUE : FALSE;
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorRequired);
            }
        }

        return $this;
    }

    /**
     * Base function for performing regular expression check
     * @param string $regex a regular expresion '/regex/' (preg_match) including delimiter
     * @param string $errorMsg
     * @return self
     */
    public function regex($regex, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = (preg_match($regex, $this->currentObj->value) === 1) ? TRUE : FALSE;
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorRegex);
            }
        }

        return $this;
    }

    /**
     * Validates a Date in yyyy-mm-dd format
     * @param string $errorMsg
     * @return self
     */
    public function date($errorMsg = NULL)
    {
        $this->regex($this->patternDate, (($errorMsg) ? $errorMsg : $this->errorDate));
        return $this;
    }

    /**
     * Validates an email address
     * @param string $errorMsg
     * @return self
     */
    public function email($errorMsg = NULL)
    {
        $this->regex($this->patternEmail, (($errorMsg) ? $errorMsg : $this->errorEmail));
        return $this;
    }

    /**
     * Validates a URL address
     * @param string $errorMsg
     * @return self
     */
    public function url($errorMsg = NULL)
    {
        $this->regex($this->patternUrl, (($errorMsg) ? $errorMsg : $this->errorUrl));
        return $this;
    }

    /**
     * Checks if value is float ( +  -  . ) permited, not working with locale settings (.|,)
     * @param string $errorMsg
     * @return self
     */
    public function numberFloat($errorMsg = NULL)
    {
        $this->regex($this->patternNumberFloat, (($errorMsg) ? $errorMsg : $this->errorNumberFloat));
        if ($this->isLastRuleValid) {
            $this->currentObj->value = floatval($this->currentObj->value);
        }
        return $this;
    }

    /**
     * Checks if value is integer ( +  - ) permited
     * @param string $errorMsg
     * @return self
     */
    public function numberInteger($errorMsg = NULL)
    {
        $this->regex($this->patternNumberInteger, (($errorMsg) ? $errorMsg : $this->errorNumberInteger));
        if ($this->isLastRuleValid) {
            $this->currentObj->value = intval($this->currentObj->value, 10);
        }
        return $this;
    }

    /**
     * /^[A-Z\_0-9]+$/i
     * @param string $errorMsg
     * @return self
     */
    public function safeChars($errorMsg = NULL)
    {
        $this->regex($this->patternSafeChars, (($errorMsg) ? $errorMsg : $this->errorSafeChars));
        return $this;
    }

    /**
     * /^[A-Z\_\/0-9\-]+[^\/]\.[A-Z]+$/i
     * @param string $errorMsg
     * @return self
     */
    public function safePath($errorMsg = NULL)
    {
        $this->regex($this->patternSafePath, (($errorMsg) ? $errorMsg : $this->errorSafePath));
        return $this;
    }

    /**
     * Check if value === $value2
     * @param string $value2
     * @param string $errorMsg
     * @return self
     */
    public function equal($value2, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = ($value2 === $this->currentObj->value);

            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorEqual);
            }
        }

        return $this;
    }

    /**
     * Check if value !== $value2
     * @param string $value2 
     * @param string $errorMsg
     * @return self
     */
    public function notEqual($value2, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = ($value2 !== $this->currentObj->value);

            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorEqual);
            }
        }

        return $this;
    }

    /**
     * Perform mb_strlen on value
     * @param int $size the maximum string size (inclusive)
     * @param string $errorMsg
     * @return self
     */
    public function maxSize($size, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = (mb_strlen($this->currentObj->value) <= $size);
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorMaxSize, $size);
            }
        }

        return $this;
    }

    /**
     * Perform mb_strlen on value
     * @param int $size the minimum string size (inclusive)
     * @param string $errorMsg
     * @return self
     */
    public function minSize($size, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = (mb_strlen($this->currentObj->value) >= $size);
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorMinSize, $size);
            }
        }

        return $this;
    }

    /**
     * Check if mb_strlen is equal $size
     * @param int $size string length size
     * @param string $errorMsg
     * @return self
     */
    public function equalSize($size, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = (mb_strlen($this->currentObj->value) === $size);
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorMinSize, $size);
            }
        }

        return $this;
    }

    /**
     * Max number (inclusive)
     * @param number $max max number (inclusive)
     * @param string $errorMsg
     * @return self
     */
    public function numberMax($max, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = ($this->currentObj->value <= $max) ? TRUE : FALSE;
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorNumberMax, $max);
            }
        }

        return $this;
    }

    /**
     * Minimal number (inclusive)
     * @param number $min minimal number (inclusive)
     * @param string $errorMsg
     * @return self
     */
    public function numberMin($min, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = ($this->currentObj->value >= $min) ? TRUE : FALSE;
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorNumberMin, $min);
            }
        }

        return $this;
    }

    /**
     * Convert value to numeric representation of bool - 1,0
     * @return self
     */
    public function checkbox()
    {
        if ($this->currentObj->value !== '') {
            $acceptable = array('yes', 'on', '1', 'true');
            $bool = in_array($this->currentObj->value, $acceptable, true);
            $this->currentObj->value = (int) $bool;
        } else {
            $this->currentObj->value = 0;
        }

        return $this;
    }

    /**
     * Check if value exists as a key in $params array
     * @param array $params
     * @param string $errorMsg
     * @return self
     */
    public function in(array $params, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = array_key_exists($this->currentObj->value, $params);
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorIn);
            }
        }

        return $this;
    }

    /**
     * Check if defined condition is boolean TRUE
     * @param bool $bool condition
     * @param string $errorMsg
     * @return self
     */
    public function assertTrue($bool, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = ((bool) $bool === true) ? true : false;
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorRegex);
            }
        }

        return $this;
    }

    /**
     * Check if defined condition is boolean FALSE
     * @param bool $bool condition
     * @param string $errorMsg
     * @return self
     */
    public function assertFalse($bool, $errorMsg = NULL)
    {
        if ($this->preCheck()) {
            $this->isLastRuleValid = ((bool) $bool === false) ? true : false;
            if (!$this->isLastRuleValid) {
                $this->setErrorMsg($errorMsg, $this->errorRegex);
            }
        }

        return $this;
    }

    /**
     * If value is not set ('') it will set $value
     * so use it only if not ->required()
     * @param string $value
     * @return self
     */
    public function defaultValue($value)
    {
        if ($this->currentObj->value === '') {
            $this->currentObj->value = $value;
        }

        return $this;
    }

    /**
     * Sanitaze value with defined callbacks in parameters
     * @return self
     */
    public function o()
    {
        if ($this->currentObj->value !== '') {
            $functions_name = func_get_args();

            foreach ($functions_name as $function_name) {
                $this->currentObj->value = $this->sanitaze($this->currentObj->value, $function_name);
            }
        }

        return $this;
    }

    /**
     * Run defined $input into $function_name
     * @param string $input
     * @param callable $function_name
     * @return string
     * @throws \Exception
     */
    private function sanitaze($input, $function_name)
    {
        if (function_exists($function_name)) {
            return call_user_func($function_name, $input);
        } else {
            throw new \Exception('Sanitize function cannot be found');
        }
    }

}
