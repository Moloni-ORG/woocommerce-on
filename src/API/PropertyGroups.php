<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class PropertyGroups extends EndpointAbstract
{
    /**
     * Get multiple property groups
     *
     * @param array|null $variables
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function queryPropertyGroups(?array $variables = []): array
    {
        $query = self::loadQuery('propertyGroups');

        return Curl::complex('propertyGroups', $query, $variables);
    }

    /**
     * Get a single property group
     *
     * @param array $variables
     *
     * @return array|bool
     *
     * @throws APIExeption
     */
    public static function queryPropertyGroup(array $variables = [])
    {
        $query = self::loadQuery('propertyGroup');

        return Curl::simple('propertyGroup', $query, $variables);
    }

    /**
     * Create a property group
     *
     * @param array $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPropertyGroupCreate(array $variables = [])
    {
        $query = self::loadMutation('propertyGroupCreate');

        return Curl::simple('propertyGroupCreate', $query, $variables);
    }

    /**
     * Create a single property (with its values) in an existing group
     *
     * @param array $variables
     *
     * @return array|bool
     *
     * @throws APIExeption
     */
    public static function mutationPropertyCreate(array $variables = [])
    {
        $query = self::loadMutation('propertyCreate');

        return Curl::simple('propertyCreate', $query, $variables);
    }

    /**
     * Create a single property value in an existing property
     *
     * @param array $variables
     *
     * @return array|bool
     *
     * @throws APIExeption
     */
    public static function mutationPropertyValueCreate(array $variables = [])
    {
        $query = self::loadMutation('propertyValueCreate');

        return Curl::simple('propertyValueCreate', $query, $variables);
    }
}
