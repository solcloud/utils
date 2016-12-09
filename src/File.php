<?php

namespace Solcloud\Utils;

use Exception;

class File
{

    /**
     * @param string $fileName
     * @return string
     * @throws Exception if file cannot be read
     */
    public static function getContent(string $fileName): string
    {
        $falseIfError = file_get_contents($fileName);
        if ($falseIfError === false) {
            throw new Exception("Cannot get contents from '{$fileName}'");
        }

        return $falseIfError;
    }

}
