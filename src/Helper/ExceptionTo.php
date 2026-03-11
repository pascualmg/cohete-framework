<?php

namespace Cohete\Helper;

use Throwable;

class ExceptionTo
{
    /**
     * @return array{name: string, code: int|string, message: string, file: string, line: int, trace: array<mixed>}
     */
    public static function array(Throwable $throwable): array
    {
        return [
            'name' => $throwable::class,
            'code' => $throwable->getCode(),
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => array_map(
                fn (mixed $item) => json_decode((string) json_encode($item), true),
                $throwable->getTrace()
            )
        ];
    }

    /**
     * @return array{name: string, code: int|string, message: string, file: string, line: int, shortTrace: mixed}
     */
    public static function arrayWithShortTrace(Throwable $throwable): array
    {
        return [
            'name' => $throwable::class,
            'code' => $throwable->getCode(),
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'shortTrace' => $throwable->getTrace()[0] ?? []
        ];
    }
}
