<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class Countries extends EndpointAbstract
{
    /**
     * Gets all countries
     *
     * @param array|null $variables
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryCountries(?array $variables = []): array
    {
        $query = self::loadQuery('countries');

        return Curl::simple('countries', $query, $variables);
    }

    /**
     * Gets country info
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryCountry(?array $variables = []): array
    {
        $query = self::loadQuery('country');

        return Curl::simple('country', $query, $variables);
    }
}
