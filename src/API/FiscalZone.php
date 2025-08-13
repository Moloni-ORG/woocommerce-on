<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class FiscalZone extends EndpointAbstract
{
    /**
     * Get settings for a fiscal zone
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryFiscalZoneTaxSettings(?array $variables = []): array
    {
        $query = self::loadQuery('fiscalZoneTaxSettings');

        return Curl::simple('fiscalZoneTaxSettings', $query, $variables);
    }
}
