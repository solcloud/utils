<?php

namespace Solcloud\Utils;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class DateFactory
{

    private $outputFormat = 'd.m.Y H:i:s';
    private $dbFormat = 'Y-m-d H:i:s';
    private $timeZone;

    public function __construct($timeZone = false)
    {
        $this->setTimeZone($timeZone ? $timeZone : date_default_timezone_get());
    }

    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    public function getDbFormat()
    {
        return $this->dbFormat;
    }

    public function setOutputFormat($outputFormat)
    {
        $this->outputFormat = $outputFormat;
    }

    public function setDbFormat($dbFormat)
    {
        $this->dbFormat = $dbFormat;
    }

    /**
     * @return DateTime
     */
    public function now()
    {
        return new DateTime;
    }

    /**
     * @return DateTime
     * @throws InvalidArgumentException when invalid format given
     */
    public function fromTimestamp($timestamp)
    {
        if (is_int($timestamp)) {
            $falseIfFail = $this->now()->setTimestamp($timestamp);
            if ($falseIfFail) {
                return $falseIfFail;
            }
        }

        throw new InvalidArgumentException("Cannot create from timestamps, use valid integer for timestamps");
    }

    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param string $timeZone
     * @throws InvalidArgumentException
     */
    public function setTimeZone($timeZone)
    {
        if (in_array($timeZone, DateTimeZone::listIdentifiers(), true)) {
            $this->timeZone = $timeZone;
        } else {
            throw new InvalidArgumentException("Invalid timezone ({$timeZone}) given");
        }
    }

}
