<?php

namespace MoloniOn;

use MoloniOn\API\Companies;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Models\Auth;
use MoloniOn\Models\Settings;

/**
 * Class Start
 * This is one of the main classes of the module
 * Every call should pass here before
 * This will render the login form or the company form, or it will return a bool
 * This will also handle the tokens
 * @package Moloni
 */
class Start
{
    public function handleRequest(): bool
    {
        $this->loadSettings();

        global $wpdb;

        $tokensRow = Auth::getTokensRow();

        // A stale Connect form (old tab, second admin) must not wipe a store that is already connected
        if (!empty($_POST['molonion_connect']) && empty($tokensRow['refresh_token'])) {
            if ($this->startConnect()) {
                wp_die();
            }

            return false;
        }
        $code = trim(sanitize_text_field($_GET['code'] ?? ''));
        $oauthError = trim(sanitize_text_field($_GET['error'] ?? ''));
        // Only a store waiting for tokens handles a callback, so a stray link can't sign out a connected one
        $awaitingCallback = !empty($tokensRow) && empty($tokensRow['refresh_token']);

        if (!empty($oauthError) && $awaitingCallback) {
            $this->loginForm(__("We couldn't connect your store to Moloni ON. Please try again.", 'moloni-on'));
            return false;
        }

        if (!empty($code) && $awaitingCallback) {
            if (!empty($tokensRow['client_secret'])) {
                try {
                    $login = Curl::login($code, $tokensRow['client_id'], $tokensRow['client_secret']);

                    if (isset($login['accessToken']) && isset($login['refreshToken'])) {
                        Auth::setTokens($login['accessToken'], $login['refreshToken']);
                    }
                } catch (APIExeption $e) {
                    $e->showError();
                }
            } else {
                $state = trim(sanitize_text_field($_GET['state'] ?? ''));
                $stored = get_transient(self::getOauthTransientKey());

                if (!is_array($stored) || empty($stored['state']) || empty($state) || !hash_equals($stored['state'], $state)) {
                    $this->loginForm(__("We couldn't connect your store to Moloni ON. Please try again.", 'moloni-on'));
                    return false;
                }

                delete_transient(self::getOauthTransientKey());

                try {
                    $login = Curl::login($code, $tokensRow['client_id'], '', $stored['verifier'], $this->getRedirectUri());

                    if (isset($login['accessToken']) && isset($login['refreshToken'])) {
                        Auth::setTokens($login['accessToken'], $login['refreshToken']);
                    }
                } catch (APIExeption $e) {
                    $e->showError();
                }
            }
        }

        if (!Auth::refreshTokens()) {
            $this->loginForm();
            return false;
        }

        $tokensRow = Auth::getTokensRow();

        if (isset($_GET['companyId'])) {
            $selectedCompanyId = (int)(sanitize_text_field($_GET['companyId']));

            $wpdb->update(Context::getTableName() . "_api",
                ['company_id' => $selectedCompanyId],
                ['id' => $tokensRow['id']]
            );

            $tokensRow['company_id'] = $selectedCompanyId;
        }

        Context::$MOLONI_ON_SESSION_ID = $tokensRow['id'];
        Context::$MOLONI_ON_ACCESS_TOKEN = $tokensRow['main_token'];

        if (empty($tokensRow['company_id'])) {
            $this->companiesForm();
            return false;
        }

        Context::$MOLONI_ON_COMPANY_ID = (int)$tokensRow['company_id'];

        $this->loadCompany();

        return true;
    }

    public function isFullyAuthed(): bool
    {
        $this->loadSettings();

        if (!Auth::refreshTokens()) {
            return false;
        }

        $tokensRow = Auth::getTokensRow();

        Context::$MOLONI_ON_SESSION_ID = $tokensRow['id'];
        Context::$MOLONI_ON_ACCESS_TOKEN = $tokensRow['main_token'];

        if (empty($tokensRow['company_id'])) {
            return false;
        }

        Context::$MOLONI_ON_COMPANY_ID = (int)$tokensRow['company_id'];

        $this->loadCompany();

        return true;
    }

    //          Form pages          //

    /**
     * Shows a login form
     *
     * @param string $error Optional error message, read by the LoginForm template
     */
    private function loginForm(string $error = '')
    {
        include(MOLONI_ON_TEMPLATE_DIR . 'LoginForm.php');
    }

    /**
     * Draw all companies available to the user
     * Except the
     */
    private function companiesForm()
    {
        include(MOLONI_ON_TEMPLATE_DIR . 'CompanySelect.php');
    }

    //          Auth          //

    /**
     * Starts an OAuth public-client connection: registers (or reuses) a public client,
     * then redirects to the Moloni ON authorization screen with PKCE.
     *
     * @return bool True when the browser was redirected to Moloni ON, false when a login form (with error) was shown instead
     */
    private function startConnect(): bool
    {
        $redirectUri = $this->getRedirectUri();
        $clientId = $this->registerOauthClient($redirectUri);

        if (empty($clientId)) {
            return false;
        }

        $verifier = self::base64UrlEncode(random_bytes(32));
        $challenge = self::base64UrlEncode(hash('sha256', $verifier, true));
        $state = self::base64UrlEncode(random_bytes(16));

        set_transient(self::getOauthTransientKey(), ['state' => $state, 'verifier' => $verifier], 10 * MINUTE_IN_SECONDS);

        Auth::setClient($clientId, '');

        $url = Context::configs()->get('api_url') . '/auth/authorize';
        $url .= '?response_type=code';
        $url .= '&client_id=' . rawurlencode($clientId);
        $url .= '&redirect_uri=' . rawurlencode($redirectUri);
        $url .= '&code_challenge=' . rawurlencode($challenge);
        $url .= '&code_challenge_method=S256';
        $url .= '&state=' . rawurlencode($state);

        wp_redirect($url);

        return true;
    }

    /**
     * Registers the public client for this store. The server returns the same client for the same
     * name and redirect URI, so nothing is kept outside the auth table. Shows the login form (with
     * error) and returns an empty string when registration fails.
     */
    private function registerOauthClient(string $redirectUri): string
    {
        try {
            $registered = Curl::register($redirectUri);
        } catch (APIExeption $e) {
            $this->loginForm($e->getMessage());
            return '';
        }

        return $registered['client_id'] ?? '';
    }

    /**
     * Stable redirect_uri for the OAuth flow: must match the one registered byte-for-byte,
     * so (unlike Context::getAdminUrl()) it carries no rotating nonce.
     */
    private function getRedirectUri(): string
    {
        // A TLS-terminating proxy can hide HTTPS from PHP, so an https site always registers an https URI
        $scheme = wp_parse_url(home_url(), PHP_URL_SCHEME) === 'https' ? 'https' : 'admin';

        return admin_url('admin.php?page=' . Context::getPageName(), $scheme);
    }

    private static function getOauthTransientKey(): string
    {
        return 'molonion_oauth_' . get_current_user_id();
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function loadSettings()
    {
        Settings::loadToContext();
    }

    private function loadCompany()
    {
        try {
            $company = Companies::queryCompany()['data']['company']['data'] ?? [];
        } catch (APIExeption $e) {
            $company = [];
        }

        Context::setCompany($company);
    }
}
