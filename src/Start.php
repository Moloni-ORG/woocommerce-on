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
        global $wpdb;

        $developerId = trim(sanitize_text_field($_POST['developer_id'] ?? ''));
        $clientSecret = trim(sanitize_text_field($_POST['client_secret'] ?? ''));

        if (!empty($developerId) && !empty($clientSecret)) {
            $this->redirectToApi($developerId, $clientSecret);
            wp_die();
        }

        $tokensRow = Auth::getTokensRow();
        $code = trim(sanitize_text_field($_GET['code'] ?? ''));

        if (!empty($code)) {
            try {
                $login = Curl::login($code, $tokensRow['client_id'], $tokensRow['client_secret']);

                if (isset($login['accessToken']) && isset($login['refreshToken'])) {
                    Auth::setTokens($login['accessToken'], $login['refreshToken']);
                }
            } catch (APIExeption $e) {
                $e->showError();
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

        $this->loadSettings();
        $this->loadCompany();

        return true;
    }

    public function isFullyAuthed(): bool
    {
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

        $this->loadSettings();
        $this->loadCompany();

        return true;
    }

    //          Form pages          //

    /**
     * Shows a login form
     */
    private function loginForm()
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
     * Redirects to API
     *
     * @return void
     */
    private function redirectToApi(string $developerId, string $clientSecret)
    {
        Auth::setClient($developerId, $clientSecret);

        $url = Context::configs()->get('api_url');
        $url .= '/auth/authorize?apiClientId=' . $developerId;
        $url .= '&redirectUri=' . urlencode(Context::getAdminUrl());

        wp_redirect($url);
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
