<?php

namespace MoloniOn;

use MoloniOn\Exceptions\APIExeption;

class Curl
{
    /**
     * Hold the request log
     *
     * @var array
     */
    private static $logs = [];

    /**
     * Plugin user agent ID
     *
     * @var string
     */
    private static $userAgent = 'WordpressPlugin/4.0';

    /**
     * Makes a simple API post request
     *
     * @param $action
     * @param $query
     * @param array|null $variables
     *
     * @return mixed
     * @throws APIExeption
     */
    public static function simple($action, $query, ?array $variables = [])
    {
        if (Context::$MOLONI_ON_COMPANY_ID) {
            $variables['companyId'] = Context::$MOLONI_ON_COMPANY_ID;
        }

        $data = ['query' => $query];

        if (!empty($variables)) {
            $data['variables'] = $variables;
        }

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . Context::$MOLONI_ON_ACCESS_TOKEN,
                'user-agent' => self::$userAgent
            ],
            'body' => json_encode($data),
            'timeout' => 45
        ];

        $response = wp_remote_post(Context::configs()->get('api_url'), $args);
        $raw = wp_remote_retrieve_body($response);
        $parsed = json_decode($raw, true);

        $log = [
            'url' => $action,
            'sent' => $variables,
            'received' => $parsed
        ];

        self::$logs[] = $log;

        if (empty($parsed['errors']) && empty($parsed['data'][$action]['errors'])) {
            return $parsed;
        }

        if (!empty($parsed['data'][$action]['data'])) {
            return $parsed;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new APIExeption(__('Oops, an error was encountered...', 'moloni-on'), $log);
    }

    /**
     * Uploads a file
     *
     * @param $headers
     * @param $payload
     * @return array|\WP_Error
     */
    public static function simpleCustomPost($headers, $payload)
    {
        return wp_remote_post(
            Context::configs()->get('api_url'),
            [
                'headers' => $headers,
                'body' => $payload,
                'timeout' => 45,
                'user-agent' => self::$userAgent
            ]
        );
    }

    /**
     * Gets all values from a query and loops its pages of results
     *
     * @param $action
     * @param $query
     * @param $variables
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function complex($action, $query, $variables): array
    {
        /** To get all items we need to paginate */

        $page = 1;
        $array = [];

        do {
            $variables['options']['pagination']['qty'] = 50;
            $variables['options']['pagination']['page'] = $page;

            $fetch = self::simple($action, $query, $variables);

            $pagination = $fetch['data'][$action]['options']['pagination'] ?? ['count' => 0, 'qty' => 0, 'page' => 0];
            $results = $fetch['data'][$action]['data'] ?? [];

            $array = array_merge($array, $results);

            $page++;
        } while (($pagination['count'] > ($pagination['qty'] * $pagination['page'])) && $page <= 1000);

        return $array;
    }

    /**
     * Returns the last curl request made from the logs
     *
     * @return mixed
     */
    public static function getLog()
    {
        return end(self::$logs);
    }

    /**
     * Registers a public OAuth client (dynamic client registration) for the given redirect URI
     *
     * @param string $redirectUri
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function register(string $redirectUri)
    {
        $url = Context::configs()->get('api_url') . '/auth/register';

        $body = [
            // The store's host makes the name unique, so the server's dedup finds this store's client
            // again, and the consent screen shows which store is connecting
            'client_name' => 'Moloni ON WooCommerce (' . wp_parse_url($redirectUri, PHP_URL_HOST) . ')',
            'redirect_uris' => [$redirectUri],
        ];

        $response = wp_remote_post(
            $url,
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($body),
                'timeout' => 45,
                'user-agent' => self::$userAgent
            ]
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new APIExeption($response->get_error_message(), ['code' => $response->get_error_code(), 'data' => $response->get_error_data(), 'message' => $response->get_error_message(),]);
        }

        $raw = wp_remote_retrieve_body($response);
        $parsed = json_decode($raw, true);

        if (!empty($parsed['client_id'])) {
            return $parsed;
        }

        $log = [
            'url' => $url,
            'sent' => $body,
            'received' => $parsed
        ];

        if (($parsed['error'] ?? '') === 'invalid_redirect_uri') {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new APIExeption(__('Your store must use HTTPS to connect to Moloni ON.', 'moloni-on'), $log);
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new APIExeption(__('Oops, an error was encountered...', 'moloni-on'), $log);
    }

    /**
     * Do a login request to the API
     *
     * @param $code
     * @param $clientId
     * @param $clientSecret
     * @param string $codeVerifier PKCE verifier, only used for public clients (empty $clientSecret)
     * @param string $redirectUri Must match the one used to obtain $code, only needed alongside $codeVerifier
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function login($code, $clientId, $clientSecret, string $codeVerifier = '', string $redirectUri = '')
    {
        $url = Context::configs()->get('api_url') . '/auth/grant';

        $fields = [
            'grantType' => 'authorization_code',
            'apiClientId' => $clientId,
            'code' => $code,
        ];

        if (!empty($clientSecret)) {
            $fields['clientSecret'] = $clientSecret;
        }

        $postFields = http_build_query($fields);

        if (!empty($codeVerifier)) {
            $postFields .= '&code_verifier=' . rawurlencode($codeVerifier);
        }

        if (!empty($redirectUri)) {
            $postFields .= '&redirect_uri=' . rawurlencode($redirectUri);
        }

        $response = wp_remote_post(
            $url,
            ['body' => $postFields, 'timeout' => 45]
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new APIExeption($response->get_error_message(), ['code' => $response->get_error_code(), 'data' => $response->get_error_data(), 'message' => $response->get_error_message(),]);
        }

        $raw = wp_remote_retrieve_body($response);

        $parsed = json_decode($raw, true);

        if (!isset($parsed['error'])) {
            return $parsed;
        }

        $log = [
            'url' => $url,
            'sent' => $postFields,
            'received' => $parsed
        ];

        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new APIExeption(__('Invalid credentials', 'moloni-on'), $log);
    }

    /**
     * Refresh the session tokens
     *
     * @param $clientId
     * @param $clientSecret
     * @param $refreshToken
     *
     * @return bool|mixed
     */
    public static function refresh($clientId, $clientSecret, $refreshToken)
    {
        $url = Context::configs()->get('api_url') . '/auth/grant';

        $fields = [
            'grantType' => 'refresh_token',
            'apiClientId' => $clientId,
            'refreshToken' => $refreshToken,
        ];

        if (!empty($clientSecret)) {
            $fields['clientSecret'] = $clientSecret;
        }

        $postFields = http_build_query($fields);

        $response = wp_remote_post(
            $url,
            ['body' => $postFields, 'timeout' => 45, 'user-agent' => self::$userAgent]
        );
        $raw = wp_remote_retrieve_body($response);

        $res_txt = json_decode($raw, true);

        if (!isset($res_txt['error'])) {
            return ($res_txt);
        }

        return false;
    }

}
