<?php

namespace Solcloud\Utils;

use Exception;

class Exec
{
    public static function std(string $command, &$stdIn, &$stdErr = null): string
    {
        $stdOut = null;
        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        self::call(
            $command,
            $descriptors,
            function (array &$pipes) use (&$stdIn, &$stdOut, &$stdErr): void {
                if (@fwrite($pipes[0], $stdIn) === false) {
                    throw new Exception("Stdin write failed");
                }
                if (@fclose($pipes[0]) === false) {
                    throw new Exception("Stdin close failed");
                }

                $stdOut = stream_get_contents($pipes[1]);
                if ($stdOut === false) {
                    throw new Exception('Stdout read failed');
                }
                if (@fclose($pipes[1]) === false) {
                    throw new Exception("Stdout close failed");
                }
                $stdErr = stream_get_contents($pipes[2]);
            }
        );

        return $stdOut;
    }

    /**
     * @param callable $setup function (array &$pipes): false|void {} false if process should be terminate with $killSignal
     * @throws Exception
     */
    public static function call(string $command, array $descriptors, callable $setup, int $killSignal = 15): void
    {
        $pipes = null;
        $resource = proc_open($command, $descriptors, $pipes);
        if (!is_resource($resource)) {
            throw new Exception('Proc open not resource');
        }

        if ($setup($pipes) === false) {
            proc_terminate($resource, $killSignal);
        } else {
            $statusCode = proc_close($resource);
            if ($statusCode !== 0) {
                throw new Exception("Command '{$command}' failed with status code '{$statusCode}'");
            }
        }
    }

}
