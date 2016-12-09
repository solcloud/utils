<?php

declare(strict_types = 1);

namespace Solcloud\Utils;

use Dibi\Connection;
use Dibi\DriverException;

class DibiFactory
{

    /**
     * @var array
     */
    protected $options = [];

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function create(): Connection
    {
        return new Connection($this->options);
    }

    public function reconnect(Connection $dibi): void
    {
        try {
            $dibi->query('SELECT 1')->fetch();
        } catch (DriverException $exIgnore) {
            $dibi->connect();
        }
    }

}
