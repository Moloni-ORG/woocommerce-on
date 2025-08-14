<?php

namespace MoloniOn\API\Abstracts;

use MoloniOn\Exceptions\APIExeption;

abstract class EndpointAbstract
{
    /**
     * Save a request cache
     *
     * @var array
     */
    protected static $responseCache = [];

    /**
     * Save a query to reduce I/O operations
     *
     * @var array
     */
    protected static $operationsCache = [];

    /**
     * Load the query from a file
     *
     * @throws APIExeption
     */
    protected static function loadQuery(string $queryName): string
    {
        if (isset(self::$operationsCache[$queryName]) && !empty(self::$operationsCache[$queryName])) {
            return self::$operationsCache[$queryName];
        }

        return self::loadFromFile('Queries', $queryName);
    }

    /**
     * Load mutation from a file
     *
     * @throws APIExeption
     */
    protected static function loadMutation(string $mutationName): string
    {
        if (isset(self::$operationsCache[$mutationName]) && !empty(self::$operationsCache[$mutationName])) {
            return self::$operationsCache[$mutationName];
        }

        return self::loadFromFile('Mutations', $mutationName);
    }

    /**
     * Load mutation or query from a file
     *
     * @throws APIExeption
     */
    private static function loadFromFile($folder, $name): string
    {
        $path = MOLONI_ON_DIR . "/src/API/$folder/$name.graphql";

        if (!file_exists($path)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new APIExeption("Query/Mutation file not found: $path");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $error = error_get_last();

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new APIExeption("Query/Mutation file failed to read: {$error['message']}");
        }

        self::$operationsCache[$name] = $contents;

        return $contents;
    }
}
