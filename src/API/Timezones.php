<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class Timezones extends EndpointAbstract
{
    /**
     * Get All Timezones from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryTimezones(?array $variables = []): array
    {
        $query = self::loadQuery('timezones');

        return Curl::complex('timezones', $query, $variables);
    }
}
