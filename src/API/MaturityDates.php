<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class MaturityDates extends EndpointAbstract
{
    /**
     * Get All Maturity Dates from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryMaturityDates(?array $variables = []): array
    {
        $query = self::loadQuery('maturityDates');

        return Curl::complex('maturityDates', $query, $variables);
    }
}
