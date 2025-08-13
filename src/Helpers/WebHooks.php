<?php

namespace MoloniOn\Helpers;

use MoloniOn\API\Hooks;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Context;
use MoloniOn\Models\Settings;

class WebHooks
{
    /**
     * Model => routes mapping
     *
     * @var string[]
     */
    private static $routes = [
        'Product' => 'products'
    ];

    /**
     * Create hook in moloni
     *
     * @param string $model
     * @param string $operation
     *
     * @throws APIExeption
     */
    public static function createHook(string $model, string $operation)
    {
        if (!isset(self::$routes[$model])) {
            return;
        }

        $namespace = Context::configs()->get('rest_api');
        $action = self::$routes[$model];
        $hash = self::createHash();

        $url = get_site_url() . "/wp-json/$namespace/$action/$hash";

        $variables['data'] = [
            'model' => $model,
            'url' => $url,
            'operation' => $operation
        ];

        Hooks::mutationHookCreate($variables);
    }

    /**
     * Deletes the created hooks
     *
     * @throws APIExeption
     */
    public static function deleteHooks()
    {
        Settings::defineValues();

        $ids = [];

        $variables = [
            'data' => [
                'search' => [
                    'field' => 'url',
                    'value' => get_site_url() . '/wp-json/'
                ]
            ]
        ];

        $query = Hooks::queryHooks($variables);

        if (!empty($query)) {
            foreach ($query as $hook) {
                $ids[] = $hook['hookId'];
            }

            Hooks::mutationHookDelete([
                'hookId' => $ids
            ]);
        }
    }

    //            Privates            //

    /**
     * Creates hash from company id
     *
     * @return string
     */
    private static function createHash(): string
    {
        return hash('md5', Context::$MOLONI_ON_COMPANY_ID);
    }
}
