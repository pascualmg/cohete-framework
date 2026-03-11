<?php

namespace Cohete\HttpServer;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RuntimeException;

use function FastRoute\simpleDispatcher;

class Router
{
    public static function fromJson(string $path): Dispatcher
    {
        self::assertNotEmpty($path);
        self::assertFileExists($path);

        $routesFromJsonFile = self::parseJsonToArray($path);

        return simpleDispatcher(
            function (RouteCollector $r) use ($routesFromJsonFile) {
                /** @var array<string, string> $routeFromJsonFile */
                foreach ($routesFromJsonFile as $routeFromJsonFile) {
                    $r->addRoute(
                        self::toUpperWords($routeFromJsonFile['method']),
                        $routeFromJsonFile['path'],
                        $routeFromJsonFile['handler']
                    );
                }
            }
        );
    }

    /**
     * @return array<int, string>
     */
    public static function toUpperWords(string $text): array
    {
        $split = preg_split("/[ ,]/", strtoupper($text));
        if ($split === false) {
            return [];
        }

        return array_values(
            array_filter(
                $split,
                fn ($value) => strlen($value) > 0
            )
        );
    }

    public static function assertNotEmpty(string $path): void
    {
        if (empty($path)) {
            throw new RuntimeException(
                "The path of the json File to load the routes is empty, maybe you have no .env or this variable is undefined? "
            );
        }
    }

    public static function assertFileExists(string $path): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException(
                "The path of the json File to load dont exists"
            );
        }
    }

    /**
     * @return array<mixed>
     */
    public static function parseJsonToArray(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException(
                " $path invalida",
            );
        }
        $file = file_get_contents($path);
        if ($file === false) {
            throw new RuntimeException(
                "Could not read file $path",
            );
        }
        try {
            /** @var array<mixed> $routesFromJsonFile */
            $routesFromJsonFile = json_decode(
                $file,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            throw new RuntimeException(
                sprintf(
                    "Invalid JSON format while loading routes from file %s \n Error: %s",
                    $path,
                    $e->getMessage()
                )
            );
        }
        return $routesFromJsonFile;
    }
}
