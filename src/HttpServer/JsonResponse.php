<?php

namespace Cohete\HttpServer;

use Cohete\Helper\ExceptionTo;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;
use Throwable;

class JsonResponse implements StatusCodeInterface
{
    private function __construct()
    {
        //factory
    }

    public static function create(int $code = self::STATUS_OK, mixed $payload = null): ResponseInterface
    {
        return new Response(
            $code,
            ['Content-type' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    public static function withPayload(mixed $payload): ResponseInterface
    {
        return self::create(200, $payload);
    }

    public static function OK(): ResponseInterface
    {
        return self::create(200, 'OK');
    }

    public static function withError(Throwable $e, int $code = self::STATUS_IM_A_TEAPOT): ResponseInterface
    {
        return self::create(
            $code,
            ExceptionTo::array($e)
        );
    }

    public static function notFound(?string $resource = null): ResponseInterface
    {
        return self::create(
            self::STATUS_NOT_FOUND,
            is_null($resource) ? "" : "$resource not found"
        );
    }

    public static function accepted(mixed $payload = 'Accepted'): ResponseInterface
    {
        return self::create(self::STATUS_ACCEPTED, $payload);
    }
}
