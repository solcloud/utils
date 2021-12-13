<?php

namespace Solcloud\Utils;

use InvalidArgumentException;

class Logger
{

    const TRACE = 'trace';
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARN = 'warn';
    const ERROR = 'error';
    const ALL_LEVELS = ['trace', 'debug', 'info', 'warn', 'error'];

    protected static $errorLevelsID = [
        'trace' => 0,
        'debug' => 1,
        'info' => 2,
        'warn' => 3,
        'error' => 4,
    ];
    protected static $errorLevelID = 2;
    protected static $display = false;
    protected static $log = null;
    protected static $remoteLogUrl = null;
    protected static $callbacks = [];
    protected static $exceptionPrintStackTrace = true;
    protected static $lastMessageIdentifier = null;

    protected static function getErrorLevel($level)
    {
        if (array_key_exists($level, static::$errorLevelsID)) {
            return static::$errorLevelsID[$level];
        }

        throw new InvalidArgumentException("Invalid level option: '{$level}' given.");
    }

    /**
     * Add custom callback to run on specified logger level
     * @param string|iterable $level iterable for multiple levels
     * @param callable $functionToRun function(string $msg, string $source, string $level):void
     */
    public static function on($level, callable $functionToRun)
    {
        if (is_iterable($level)) {
            foreach ($level as $lvl) {
                static::onHelper($lvl, $functionToRun);
            }
        } else {
            static::onHelper($level, $functionToRun);
        }
    }

    protected static function onHelper($level, callable $functionToRun)
    {
        $levelID = static::getErrorLevel($level);
        if (!isset(static::$callbacks[$levelID])) {
            static::$callbacks[$levelID] = [];
        }

        static::$callbacks[$levelID][] = $functionToRun;
    }

    public static function setLevel($level)
    {
        static::$errorLevelID = static::getErrorLevel($level);
    }

    public static function setLog($fileName = null)
    {
        if (null !== $fileName) {
            $dir = dirname($fileName);

            if (!file_exists($fileName) && !file_exists($dir) && mkdir($dir) && !is_writable($dir)) {
                throw new InvalidArgumentException("Cannot write to '{$fileName}'");
            }
        }
        static::$log = $fileName;
    }

    public static function setDisplay($bool)
    {
        static::$display = (boolean) $bool;
    }

    public static function setRemoteLogUrl($logUrl = null)
    {
        if (!function_exists('curl_version')) {
            throw new InvalidArgumentException("CURL not instaled");
        }
        static::$remoteLogUrl = $logUrl;
    }

    public static function setExceptionShouldPrintStackTrace($bool)
    {
        static::$exceptionPrintStackTrace = (boolean) $bool;
    }

    public static function setMessageIdentifier($stringableIdentifierOrNull = null)
    {
        if ($stringableIdentifierOrNull === null) {
            static::$lastMessageIdentifier = null;
            return;
        }

        static::$lastMessageIdentifier = (string) $stringableIdentifierOrNull;
    }

    public static function message($msg, $level = 'info', $source = null)
    {
        $levelID = static::getErrorLevel($level);
        if (static::$errorLevelID > $levelID) {
            return FALSE;
        }

        $msg = str_replace(["\n", "\r"], '<br>', (string) $msg);
        if (static::$lastMessageIdentifier !== null) {
            $msg = '(' . static::$lastMessageIdentifier . ') ' . $msg;
        }
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        if ($source === null && isset($caller[1])) {
            $source = $caller[1];
            if ('Solcloud\\Utils\\PsrLogger' === $source['class'] && isset($caller[2])) {
                $source = $caller[2];
            }
            $source = $source['file'] . ':' . $source['line'];
        }

        $source = $source === null ? 'Source: UNKNOWN' : $source;
        $msg = sprintf('[%s] %s @ %s, %s', date('Y-m-d H:i:s'), $msg, $source . ', ' . gethostname(), $level);

        if (static::$display === true) {
            echo $msg . PHP_EOL;
        }
        if (!is_null(static::$log)) {
            file_put_contents(static::$log, $msg . PHP_EOL, FILE_APPEND);
        }
        if (static::$remoteLogUrl) {
            static::remoteLog($msg, $level, $source);
        }

        if (isset(static::$callbacks[$levelID])) {
            foreach (static::$callbacks[$levelID] as $callback) {
                call_user_func($callback, $msg, $source, $level);
            }
        }

        return TRUE;
    }

    public static function remoteLog($msg, $level = 'info', $source = null)
    {
        $data = [
            'stream' => $level,
            'docker' => ['container_id' => getenv('CONTAINER_ID') ?: ''],
            'kubernetes' => [
                'container_name' => getenv('CONTAINER_NAME') ?: 'php',
                'namespace_name' => getenv('POD_NAMESPACE') ?: 'unknown',
                'pod_name' => getenv('POD_NAME') ?: 'unknown',
            ],
            'log' => $msg,
            'className' => $source,
            '@timestamp' => date('c'),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::$remoteLogUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        @curl_exec($ch);
        @curl_close($ch);
    }

    public static function trace($msg)
    {
        return static::message($msg, 'trace');
    }

    public static function debug($msg)
    {
        return static::message($msg, 'debug');
    }

    public static function info($msg)
    {
        return static::message($msg, 'info');
    }

    public static function warn($msg)
    {
        return static::message($msg, 'warn');
    }

    public static function error($msg)
    {
        return static::message($msg, 'error');
    }

    public static function exception($ex, $level = 'error')
    {
        //PHP5 or PHP7
        if ($ex instanceof \Exception || (interface_exists('\\Throwable', false) && $ex instanceof \Throwable)) {
            $msg = $ex->getMessage();
            if (static::$exceptionPrintStackTrace) {
                $msg .= ' StackTrace: ' . $ex->getTraceAsString();
            }
            return static::message($msg, $level, $ex->getFile() . ':' . $ex->getLine());
        }

        throw new InvalidArgumentException('Variable $ex shloud be instance of \Throwable or \Exception');
    }

}
