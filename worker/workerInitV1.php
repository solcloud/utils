<?php

use Solcloud\Utils\Logger;
use Solcloud\Utils\HashMap;

Logger::setLevel(HashMap::get('dependency.logger.debug.level', Logger::INFO));
Logger::setDisplay(HashMap::get('dependency.logger.debug.screen', false));
Logger::setRemoteLogUrl(HashMap::getOrNull('dependency.logger.debug.remoteLogUrl'));
Logger::setLog(HashMap::getOrNull('dependency.logger.debug.log'));
Logger::setExceptionShouldPrintStackTrace(HashMap::get('dependency.logger.debug.stacktrace', true));

register_shutdown_function(function () {
    $error = error_get_last();
    if (is_array($error)) {
        Logger::message($error['message'], Logger::ERROR, 'SHUTDOWN_FUNCTION ' . $error['file'] . ':' . $error['line'] . ', TYPE: ' . $error['type']);
        if (in_array($error['type'], [E_ERROR], true)) {
            // non recoverable fatal run-time errors
            if (HashMap::has('error.fatal.doNotPrintToStderr') === false) {
                fwrite(STDERR, 'cannot recover');
            }
            sleep(HashMap::get('error.fatal.sleepTimeSec', 1));
        }
    }
});

set_exception_handler(function ($ex) {
    Logger::exception($ex);
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) { // if error has been suppressed with an @
        return;
    }

    if (in_array($errno, [E_WARNING], true)) {
        Logger::message($errstr, Logger::WARN, 'ERROR_HANDLER ' . $errfile . ':' . $errline . ', TYPE: ' . $errno);
    } else {
        Logger::message($errstr, Logger::ERROR, 'ERROR_HANDLER ' . $errfile . ':' . $errline . ', TYPE: ' . $errno);
    }
});

if (HashMap::get('notify.mail.enable', false) && isset($container['redis']) && HashMap::has('notify.mail.to')) {
    /* @var $redis Redis */
    $redis = $container['redis'];

    Logger::on(HashMap::get('notify.mail.levels', Logger::ERROR), function(string $msg, string $subject, string $level) use ($redis) {
        $from = HashMap::get('notify.mail.from', 'NoName given (notify.mail.from)');
        $filenameLock = 'userNotice-' . $from . '-@@-' . md5($subject) . '-lock';
        if ($redis->exists($filenameLock)) {
            return;
        }

        $redis->set($filenameLock, 'lock', ['ex' => HashMap::get('nofify.mail.lock.expirySec', 86400 /* one day */)]);
        mail(
            HashMap::get('notify.mail.to'),
            $subject,
            implode('<br><br>', [$msg, gethostname(), $filenameLock]),
            "From: {$from} <" . HashMap::get('notify.mail.sender', HashMap::get('notify.mail.to')) . ">\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n"
        );
    });
}
