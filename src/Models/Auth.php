<?php

namespace MoloniOn\Models;

use MoloniOn\Context;
use MoloniOn\Curl;
use MoloniOn\Services\Mails\AuthenticationExpired;

class Auth
{
    /**
     * Return the row of moloni_api table with all the session details
     *
     * @global $wpdb
     */
    public static function getTokensRow()
    {
        global $wpdb;

        $tableName = Context::getTableName();

        return $wpdb->get_row("SELECT * FROM {$tableName}_api ORDER BY id DESC", ARRAY_A);
    }

    /**
     * Adds client id and secret to the database
     *
     * @param string $clientId
     * @param string $clientSecret
     */
    public static function setClient(string $clientId, string $clientSecret)
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $wpdb->query("TRUNCATE {$tableName}_api");
        $wpdb->insert("{$tableName}_api", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]);
    }

    /**
     * Clear api table and set new access and refresh token
     *
     * @param string $accessToken
     * @param string $refreshToken
     *
     * @global $wpdb
     */
    public static function setTokens(string $accessToken, string $refreshToken): void
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $wpdb->update("{$tableName}_api",
            ['main_token' => $accessToken, 'refresh_token' => $refreshToken],
            ['id' => 1]
        );
    }

    /**
     * Checks if tokens need to be refreshed and refreshes them
     * If it fails, log user out
     *
     * @param int $retryNumber Number of current retries
     *
     * @return bool
     * @global $wpdb
     */
    public static function refreshTokens(int $retryNumber = 0): bool
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $tokensRow = self::getTokensRow() ?? [];

        if (empty($tokensRow)) {
            return false;
        }

        $access_expire = $tokensRow['access_expire'] ?? false;
        $refresh_expire = $tokensRow['refresh_expire'] ?? false;

        if ($refresh_expire && $refresh_expire < time()) {
            $wpdb->query("TRUNCATE {$tableName}_api");

            return false;
        }

        if (!$access_expire || $access_expire < time()) {
            $results = Curl::refresh($tokensRow['client_id'], $tokensRow['client_secret'], $tokensRow['refresh_token']);

            if (isset($results['accessToken'], $results['refreshToken'])) {
                $wpdb->update("{$tableName}_api",
                    [
                        'main_token' => $results['accessToken'],
                        'refresh_token' => $results['refreshToken'],
                        'access_expire' => time() + 3000,
                        'refresh_expire' => time() + 864000
                    ],
                    ['id' => $tokensRow['id']]
                );
            } else {
                $recheckTokens = self::getTokensRow();

                if (empty($recheckTokens) ||
                    empty($recheckTokens['main_token']) ||
                    empty($recheckTokens['refresh_token']) ||
                    $recheckTokens['main_token'] === $tokensRow['main_token'] ||
                    $recheckTokens['refresh_token'] === $tokensRow['refresh_token']) {
                    if ($retryNumber <= 3) {
                        $retryNumber++;

                        return self::refreshTokens($retryNumber);
                    }

                    // Send e-mail notification if email is set
                    $alertEmail = Context::settings()->getString('alert_email');

                    if (!empty($alertEmail)) {
                        new AuthenticationExpired($alertEmail);
                    }

                    // Translators: %1$s is the number of tries.
                    Context::logger()->error(sprintf(__('Reseting tokens after %1$s tries', 'moloni-on'), $retryNumber), [
                        'tag' => 'service:refreshtokens:error',
                    ]);

                    self::resetTokens();

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Define constants from database
     */
    public static function loadToContext()
    {
        $tokensRow = self::getTokensRow();

        Context::$MOLONI_ON_SESSION_ID = $tokensRow['id'] ?? '';
        Context::$MOLONI_ON_ACCESS_TOKEN = $tokensRow['main_token'] ?? '';

        if (!empty($tokensRow['company_id'])) {
            Context::$MOLONI_ON_COMPANY_ID = (int)$tokensRow['company_id'];
        }
    }

    /**
     * Resets database table
     */
    public static function resetTokens(): void
    {
        global $wpdb;

        Context::resetSession();
        $tableName = Context::getTableName();

        $wpdb->query("TRUNCATE {$tableName}_api");
    }
}
