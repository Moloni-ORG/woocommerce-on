<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class GeographicZones extends EndpointAbstract
{
    /**
     * Gets geographic zones
     *
     * @param array|null $variables
     *
     * @return array Api data
     * @throws APIExeption
     */
    public static function queryGeographicZones(?array $variables = []): array
    {
        $query = self::loadQuery('geographicZones');

        return Curl::complex('geographicZones', $query, $variables);
    }
}
