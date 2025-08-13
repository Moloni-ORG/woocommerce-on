<?php


namespace MoloniOn\API;


use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class Languages extends EndpointAbstract
{
    /**
     * Gets languages.
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryLanguage(?array $variables = []): array
    {
        $query = self::loadQuery('language');

        return Curl::simple('language', $query, $variables);
    }

    /**
     * Gets language info
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryLanguages(?array $variables = []): array
    {
        $query = self::loadQuery('languages');

        return Curl::complex('languages', $query, $variables);
    }
}
