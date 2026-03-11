<?php

namespace Cohete\HttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

interface HttpRequestHandler
{
    /**
     * @param array<string, string>|null $routeParams
     * @return ResponseInterface|PromiseInterface<ResponseInterface>
     */
    public function __invoke(ServerRequestInterface $request, ?array $routeParams): ResponseInterface|PromiseInterface;
}
