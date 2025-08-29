<?php

namespace MoloniOn;

use MoloniOn\Enums\Boolean;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\Core\MoloniException;
use MoloniOn\Exceptions\DocumentError;
use MoloniOn\Exceptions\DocumentWarning;
use MoloniOn\Helpers\External;
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
use MoloniOn\Models\Logs;
use MoloniOn\Scripts\Enqueue;
use MoloniOn\Services\Documents\DownloadDocumentPDF;
use MoloniOn\Services\Documents\OpenDocument;
use MoloniOn\Services\Orders\CreateMoloniDocument;
use MoloniOn\Services\Orders\DiscardOrder;
use MoloniOn\Tools\Logger;
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
        if (wp_doing_ajax()) {
            return;
        }

        Security::verify_post_request_or_die();

        $authenticated = false;

        try {
            $authenticated = Start::login();

            /** If the user is not logged in show the login form */
            if (!$authenticated) {
                return;
            }

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

        if ($authenticated) {
            include MOLONI_ON_TEMPLATE_DIR . 'MainContainer.php';
        }
    }

    //            Actions            //

    /**
     * Create a new document
     *
     * @throws DocumentWarning|DocumentError|MoloniException
     */
    private function createDocument()
    {
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
}
