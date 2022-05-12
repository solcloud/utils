<?php

declare(strict_types=1);

namespace Solcloud\Utils\Entity\Traits;

trait ColumnBuilder
{

    public static function field(string $columnSnakeCaseName): string
    {
        if (mb_substr($columnSnakeCaseName, -3) === '_id') {
            $columnSnakeCaseName = mb_substr($columnSnakeCaseName, 0, -3);
        }

        return str_replace('_', '', lcfirst(ucwords($columnSnakeCaseName, '_')));
    }

    public static function column(string $columnSnakeCaseName, ?string $alias = null): string
    {
        $columnCamelCase = self::field($columnSnakeCaseName);

        return ($alias ?? static::ALIAS) . '.' . $columnCamelCase;
    }

    public static function join(string $targetEntity, ?string $alias = null): string
    {
        return ($alias ?? static::ALIAS) . '.' . static::JOIN_MAP[$targetEntity];
    }

}
