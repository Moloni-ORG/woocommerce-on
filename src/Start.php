<?php

namespace MoloniOn;

use MoloniOn\API\Companies;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\MoloniPlans;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Helpers\WebHooks;
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
    /** @var bool */
    private static $ajax = false;

    /**
     * Handles session, login and settings
     *
     * @param bool|null $ajax
     *
     * @return bool
     */
    public static function login(?bool $ajax = false): bool
    {
        self::$ajax = $ajax;

        $action = trim(sanitize_text_field($_REQUEST['action'] ?? ''));
        $developerId = trim(sanitize_text_field($_POST['developer_id'] ?? ''));
        $clientSecret = trim(sanitize_text_field($_POST['client_secret'] ?? ''));
        $code = trim(sanitize_text_field($_GET['code'] ?? ''));

        if (!empty($developerId) && !empty($clientSecret) && self::shouldTrustForm()) {
            self::redirectToApi($developerId, $clientSecret);
            return true;
        }

        if (!empty($code)) {
            $loginValid = false;
            $errorMessage = '';
            $errorBag = [];

            try {
                $tokensRow = Settings::getTokensRow();

                $login = Curl::login($code, $tokensRow['client_id'], $tokensRow['client_secret']);

                if ($login && isset($login['accessToken']) && isset($login['refreshToken'])) {
                    $loginValid = true;

                    Settings::setTokens($login['accessToken'], $login['refreshToken']);
                }
            } catch (APIExeption $e) {
                $errorMessage = $e->getMessage();
                $errorBag = $e->getData();
            }

            if (!$loginValid) {
                self::loginForm($errorMessage, $errorBag);
                return false;
            }
        }

        switch ($action) {
            case 'logout':
                self::logout();

                break;
            case 'saveSettings':
                self::saveSettings();

                break;
            case 'saveAutomations':
                self::saveAutomations();

                break;
        }

        $tokensRow = Settings::getTokensRow();

        if (!empty($tokensRow['main_token']) && !empty($tokensRow['refresh_token'])) {
            Settings::refreshTokens();
            Settings::defineValues();

            if (Context::$MOLONI_ON_COMPANY_ID) {
                Settings::defineConfigs();

                return true;
            }

            if (isset($_GET['companyId'])) {
                global $wpdb;

                $wpdb->update(Context::getTableName() . "_api",
                    ['company_id' => (int)(sanitize_text_field($_GET['companyId']))],
                    ['id' => Context::$MOLONI_ON_SESSION_ID]
                );

                Settings::defineValues();
                Settings::defineConfigs();

                self::afterCompanySelect();

                return true;
            }

            self::companiesForm();

            return false;
        }

        self::loginForm();

        return false;
    }

    //          Form pages          //

    /**
     * Shows a login form
     *
     * @param bool|string $error Is used in include
     * @param bool|array $errorData Is used in include
     */
    public static function loginForm($error = false, $errorData = false)
    {
        if (!self::$ajax) {
            include(MOLONI_ON_TEMPLATE_DIR . 'LoginForm.php');
        }
    }

    /**
     * Draw all companies available to the user
     * Except the
     */
    public static function companiesForm()
    {
        if (self::$ajax) {
            return;
        }

        try {
            $query = Companies::queryCompanies();

            foreach ($query['data']['companies']['data'] as $company) {
                if (empty($company['companyId'])) {
                    continue;
                }

                if (!$company['isConfirmed']) {
                    continue;
                }

                $companies[] = $company;
            }
        } catch (APIExeption $e) {
            $companies = [];
        }

        include(MOLONI_ON_TEMPLATE_DIR . 'CompanySelect.php');
    }

    //          Auth          //

    /**
     * Redirects to API
     *
     * @return void
     */
    private static function redirectToApi(string $developerId, string $clientSecret)
    {
        Settings::setClient($developerId, $clientSecret);

        $url = Context::configs()->get('api_url');
        $url .= '/auth/authorize?apiClientId=' . $developerId;
        $url .= '&redirectUri=' . urlencode(Context::getAdminUrl());

        wp_redirect($url);
    }

    /**
     * Removes plugin authentication
     *
     * @return void
     */
    private static function logout()
    {
        Settings::resetTokens();

        try {
            WebHooks::deleteHooks();
        } catch (APIExeption $e) {
        }
    }


    //          Company select          //

    /**
     * After a company has been choosen
     *
     * @return void
     */
    private static function afterCompanySelect()
    {
        try {
            $company = Companies::queryCompany()['data']['company']['data'] ?? [];

            if (MoloniPlans::hasVariants((int)($company['subscription'][0]['plan']['planId'] ?? 0))) {
                self::saveOptions(['sync_products_with_variants' => Boolean::YES]);
            } else {
                self::saveOptions(['sync_products_with_variants' => Boolean::NO]);
            }
        } catch (APIExeption $e) {
        }
    }

    //          Settings/Automations          //

    /**
     * Save plugin settings
     *
     * @return void
     */
    private static function saveSettings()
    {
        if (!self::shouldTrustForm()) {
            return;
        }

        $options = self::sanitizeSettingsValues($_POST['opt'] ?? []);

        self::saveOptions($options);

        add_settings_error('general', 'settings_updated', __('Changes saved.', 'moloni-on'), 'updated');
    }

    /**
     * Save plugin automations
     *
     * @return void
     */
    private static function saveAutomations()
    {
        if (!self::shouldTrustForm()) {
            return;
        }

        $options = self::sanitizeAutomationsValues($_POST['opt'] ?? []);

        self::saveOptions($options);

        try {
            WebHooks::deleteHooks();

            if (isset($options['hook_stock_sync']) && (int)$options['hook_stock_sync'] === Boolean::YES) {
                WebHooks::createHook('Product', 'stockChanged');
            }

            if (isset($options['hook_product_sync']) && (int)$options['hook_product_sync'] === Boolean::YES) {
                WebHooks::createHook('Product', 'create');
                WebHooks::createHook('Product', 'update');
            }
        } catch (APIExeption $e) {
        }

        add_settings_error('general', 'automations_updated', __('Changes saved.', 'moloni-on'), 'updated');
    }

    private static function saveOptions(array $options)
    {
        foreach ($options as $option => $value) {
            Settings::setOption($option, $value);
        }
    }

    private static function sanitizeSettingsValues($input): array
    {
        $output = [];

        $schema = [
            // === Text fields ===
            'company_slug' => 'text',
            'document_type' => 'text',
            'load_address_custom_address' => 'text',
            'load_address_custom_code' => 'text',
            'load_address_custom_city' => 'text',
            'exemption_reason' => 'text',
            'exemption_reason_shipping' => 'text',
            'exemption_reason_extra_community' => 'text',
            'exemption_reason_shipping_extra_community' => 'text',
            'client_prefix' => 'text',
            'vat_field' => 'text',

            // === Integers (IDs, dropdowns, etc.) ===
            'document_status' => 'int',
            'load_address' => 'int',
            'customer_language' => 'int',
            'document_set_id' => 'int',
            'load_address_custom_country' => 'int',
            'moloni_product_warehouse' => 'int',
            'measure_unit' => 'int',
            'maturity_date' => 'int',
            'payment_method' => 'int',
            'create_bill_of_lading' => 'int',
            'shipping_info' => 'int',
            'email_send' => 'int',
            'vat_validate' => 'int',
            'moloni_show_download_column' => 'int',

            // === Email ===
            'alert_email' => 'email',

            // === Date ===
            'order_created_at_max' => 'date',
        ];

        return self::sanitizer($schema, $input, $output);
    }

    private static function sanitizeAutomationsValues($input): array
    {
        $output = [];

        $schema = [
            // === Boolean flags (0/1) ===
            'sync_products_with_variants' => 'bool',
            'sync_fields_name' => 'bool',
            'sync_fields_price' => 'bool',
            'sync_fields_description' => 'bool',
            'sync_fields_visibility' => 'bool',
            'sync_fields_stock' => 'bool',
            'sync_fields_categories' => 'bool',
            'sync_fields_ean' => 'bool',
            'sync_fields_image' => 'bool',

            // === Integers (IDs, dropdowns, etc.) ===
            'invoice_auto' => 'int',
            'moloni_product_sync' => 'int',
            'moloni_stock_sync' => 'int',
            'moloni_stock_sync_warehouse' => 'int',
            'hook_product_sync' => 'int',
            'hook_stock_sync' => 'int',
            'hook_stock_sync_warehouse' => 'int',

            // === Special status ===
            'invoice_auto_status' => 'status',
        ];

        return self::sanitizer($schema, $input, $output);
    }

    private static function sanitizer(array $schema, $input, array $output): array
    {
        foreach ($schema as $key => $type) {
            $value = $input[$key] ?? null;

            switch ($type) {
                case 'bool':
                    $output[$key] = empty($value) ? 0 : 1;
                    break;

                case 'int':
                    $output[$key] = absint($value);
                    break;

                case 'email':
                    $output[$key] = sanitize_email($value);
                    break;

                case 'status':
                    $allowed = ['completed', 'pending', 'on-hold', 'processing'];
                    $value = sanitize_text_field($value);
                    $output[$key] = in_array($value, $allowed, true) ? $value : 'completed';
                    break;
                case 'date':
                case 'text':
                default:
                    $output[$key] = sanitize_text_field($value);
            }
        }

        return $output;
    }

    private static function shouldTrustForm(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'molonion-form-nonce')) {
            wp_die('Security check failed');
        }

        return true;
    }
}
