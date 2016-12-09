<?php

declare(strict_types=1);

namespace Solcloud\Utils;

use Psr\Log\AbstractLogger;

class PsrLogger extends AbstractLogger
{
    private $map = [
        'emergency' => 'error',
        'alert'     => 'warn',
        'critical'  => 'error',
        'error'     => 'error',
        'warning'   => 'warn',
        'notice'    => 'trace',
        'info'      => 'info',
        'debug'     => 'debug',
    ];

    public function log($level, $message, array $context = [])
    {
        $level = $this->map[$level] ?? 'error';

        //PHP5 or PHP7 exception
        if (is_object($message) && (
            $message instanceof \Exception ||
            (interface_exists('\\Throwable', false) && $message instanceof \Throwable)
            )
        ) {
            Logger::exception($message, $level);
            return;
        }

        Logger::message($message, $level);
    }
}
