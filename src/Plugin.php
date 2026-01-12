<?php

namespace MoloniOn;

use MoloniOn\Enums\Boolean;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\Core\MoloniException;
use MoloniOn\Exceptions\DocumentError;
use MoloniOn\Exceptions\DocumentWarning;
use MoloniOn\Helpers\Security;
use MoloniOn\Helpers\WebHooks;
use MoloniOn\Hooks\Ajax;
use MoloniOn\Hooks\OrderList;
use MoloniOn\Hooks\OrderPaid;
use MoloniOn\Hooks\OrderView;
use MoloniOn\Hooks\ProductDelete;
use MoloniOn\Hooks\ProductUpdate;
use MoloniOn\Hooks\ProductView;
use MoloniOn\Hooks\UpgradeProcess;
use MoloniOn\Hooks\WoocommerceInitialize;
use MoloniOn\Menus\Admin;
use MoloniOn\Models\Auth;
use MoloniOn\Models\Logs;
use MoloniOn\Models\Settings;
use MoloniOn\Services\Documents\DownloadDocumentPDF;
use MoloniOn\Services\Documents\OpenDocument;
use MoloniOn\Services\Orders\CreateMoloniDocument;
use MoloniOn\Services\Orders\DiscardOrder;
use MoloniOn\WebHooks\WebHook;

/**
 * Main constructor
 * Class Plugin
 * @package Moloni
 */
class Plugin
{
    private $action = '';
    private $activeTab = '';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
        $this->onStart();
        $this->actions();
    }

    //            Privates            //

    /**
     * Place to run code before starting
     *
     * @return void
     */
    private function onStart()
    {
        $this->action = sanitize_text_field($_REQUEST['action'] ?? '');
        $this->activeTab = sanitize_text_field($_GET['tab'] ?? '');

        Context::initContext();
    }

    /**
     * Starts necessary classes
     */
    private function actions()
    {
        /** Admin pages */
        new Admin($this);
        new Ajax($this);

        /** Webservices */
        new WebHook();

        /** Hooks */
        new ProductUpdate($this);
        new ProductDelete($this);
        new ProductView($this);
        new OrderView($this);
        new OrderPaid($this);
        new OrderList($this);
        new UpgradeProcess($this);
        new WoocommerceInitialize($this);
    }

    //            Public's            //

    /**
     * Main function
     * This will run when accessing the page "molonion" and the routing shoud be done here with and $_GET['action']
     */
    public function run()
    {
        if (wp_doing_ajax() || !Security::verify_user_can_access_wc()) {
            return;
        }

        /**
         * This is the plugin entry point, so we check the request's validity here
         */
        Security::verify_request_or_die();

        try {
            // "Free" actions
            switch ($this->action) {
                case 'logout':
                    $this->logout();

                    break;
                case 'saveSettings':
                    $this->saveSettings();

                    break;
                case 'saveAutomations':
                    $this->saveAutomations();

                    break;
            }

            /** If the user is not logged in show the login form */
            if (!(new Start())->handleRequest()) {
                return;
            }

            // "Authed" actions
            switch ($this->action) {
                case 'remInvoice':
                    $this->removeOrder();
                    break;

                case 'reinstallWebhooks':
                    $this->reinstallWebhooks();
                    break;

                case 'genInvoice':
                    $this->createDocument();
                    break;

                case 'remLogs':
                    $this->removeLogs();
                    break;

                case 'getInvoice':
                    $this->openDocument();
                    break;
                case 'downloadDocument':
                    $this->downloadDocument();
                    break;
            }
        } catch (MoloniException $error) {
            $pluginErrorException = $error;
        }

        include MOLONI_ON_TEMPLATE_DIR . 'MainContainer.php';
    }

    //            Actions            //

    /**
     * Create a new document
     *
     * @throws DocumentWarning|DocumentError|MoloniException
     */
    private function createDocument()
    {
        if (!$this->checkCapabilityPermissions('edit_shop_orders')) {
            return;
        }

        $service = new CreateMoloniDocument((int)(sanitize_text_field($_REQUEST['id'])));
        $orderName = $service->getOrderNumber();

        try {
            $service->run();
        } catch (DocumentWarning $e) {
            // Translators: %1$s is the order name.
            $message = sprintf(__('There was an warning when generating the document (%1$s)', 'moloni-on'), $orderName);
            $message .= ' </br>';
            $message .= $e->getMessage();

            Context::logger()->alert($message, [
                    'tag' => 'service:document:create:manual:warning',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );

            throw $e;
        } catch (DocumentError $e) {
            // Translators: %1$s is the order name.
            $message = sprintf(__('There was an error when generating the document (%1$s)', 'moloni-on'), $orderName);
            $message .= ' </br>';
            $message .= wp_strip_all_tags($e->getMessage());

            Context::logger()->error($message, [
                    'tag' => 'service:document:create:manual:error',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );

            throw $e;
        }

        if ($service->getDocumentId() > 0) {
            $viewUrl = ' <a href="' . esc_url(Context::getAdminUrl("&action=getInvoice&id={$service->getDocumentId()}")) . '" target="_BLANK">';
            $viewUrl .= __('View document', 'moloni-on');
            $viewUrl .= '</a>';

            add_settings_error('molonion', 'moloni-document-created-success', __('Document was created!', 'moloni-on') . $viewUrl, 'updated');
        }
    }

    /**
     * Open Moloni document
     *
     * @return void
     */
    private function openDocument()
    {
        $documentId = (int)(sanitize_text_field($_REQUEST['id']));

        if ($documentId > 0) {
            new OpenDocument($documentId);
        }

        add_settings_error('molonion', 'moloni-document-not-found', __('Document not found.', 'moloni-on'));
    }

    /**
     * Download Moloni document
     *
     * @return void
     */
    private function downloadDocument(): void
    {
        $documentId = (int)(sanitize_text_field($_REQUEST['id']));

        if ($documentId > 0) {
            new DownloadDocumentPDF($documentId);
        }
    }

    /**
     * Delete logs
     *
     * @return void
     */
    private function removeLogs()
    {
        Logs::removeOlderLogs();

        add_settings_error('molonion', 'moloni-rem-logs', __('Logs cleanup is complete.', 'moloni-on'), 'updated');
    }

    /**
     * Remove order from pending list
     */
    private function removeOrder()
    {
        if (!$this->checkCapabilityPermissions('edit_shop_orders')) {
            return;
        }

        $orderId = (int)(sanitize_text_field($_GET['id']));

        if (isset($_GET['confirm']) && sanitize_text_field($_GET['confirm']) === 'true') {
            $order = wc_get_order($orderId);

            $service = new DiscardOrder($order);
            $service->run();
            $service->saveLog();

            // Translators: %1$s is the order ID.
            $message = sprintf(__('Order %1$s has been marked as generated!', 'moloni-on'), $orderId);

            add_settings_error(
                'molonion',
                'moloni-order-remove-success',
                $message,
                'updated'
            );
        } else {
            // Translators: %1$s is the order ID.
            $message = sprintf(__('Do you confirm that you want to mark the order %1$s as paid?', 'moloni-on'), $orderId);

            add_settings_error(
                'molonion',
                'moloni-order-remove',
                $message . " <a href='" . esc_url(Context::getAdminUrl("action=remInvoice&confirm=true&id=$orderId")) . "'>" . __('Yes, i confirm', 'moloni-on') . "</a>"
            );
        }
    }

    /**
     * Reinstall Moloni Webhooks
     */
    private function reinstallWebhooks()
    {
        try {
            WebHooks::deleteHooks();

            if (defined('HOOK_STOCK_SYNC') && (int)HOOK_STOCK_SYNC === Boolean::YES) {
                WebHooks::createHook('Product', 'stockChanged');
            }

            if (defined('HOOK_PRODUCT_SYNC') && (int)HOOK_PRODUCT_SYNC === Boolean::YES) {
                WebHooks::createHook('Product', 'create');
                WebHooks::createHook('Product', 'update');
            }

            $msg = __('Moloni Webhooks reinstalled successfully.', 'moloni-on');
            $type = 'updated';
        } catch (APIExeption $e) {
            $msg = __('Something went wrong reinstalling Moloni Webhooks.', 'moloni-on');
            $type = 'error';
        }

        add_settings_error('molonion', 'moloni-webhooks-reinstall-error', $msg, $type);
    }

    private function logout()
    {
        Auth::loadToContext();

        try {
            WebHooks::deleteHooks();
        } catch (APIExeption $e) {
        }

        Auth::resetTokens();
    }

    /**
     * Save plugin settings
     *
     * @return void
     */
    private function saveSettings()
    {
        $options = $this->sanitizeSettingsValues($_POST['opt'] ?? []);

        $this->saveOptions($options);

        add_settings_error('general', 'settings_updated', __('Changes saved.', 'moloni-on'), 'updated');
    }

    /**
     * Save plugin automations
     *
     * @return void
     */
    private function saveAutomations()
    {
        $options = $this->sanitizeAutomationsValues($_POST['opt'] ?? []);

        $this->saveOptions($options);

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

    private function saveOptions(array $options)
    {
        foreach ($options as $option => $value) {
            Settings::setOption($option, $value);
        }
    }

    private function sanitizeSettingsValues($input): array
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

        return Security::sanitizer($schema, $input, $output);
    }

    private function sanitizeAutomationsValues($input): array
    {
        $output = [];

        $schema = [
            // === Boolean flags (0/1) ===
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

        return Security::sanitizer($schema, $input, $output);
    }

    //            Actions            //

    private function checkCapabilityPermissions($capability = ''): bool
    {
        if (current_user_can($capability)) {
            return true;
        }

        add_settings_error('molonion', 'moloni-missing-permissions', __('Insufficient permissions.', 'moloni-on'));

        return false;
    }
}
