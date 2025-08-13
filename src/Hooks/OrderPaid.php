<?php

namespace MoloniOn\Hooks;

use Exception;
use MoloniOn\Enums\AutomaticDocumentsStatus;
use MoloniOn\Enums\Boolean;
use MoloniOn\Exceptions\DocumentError;
use MoloniOn\Exceptions\DocumentWarning;
use MoloniOn\Notice;
use MoloniOn\Plugin;
use MoloniOn\Services\Mails\DocumentFailed;
use MoloniOn\Services\Orders\CreateMoloniDocument;
use MoloniOn\Start;
use MoloniOn\Context;

class OrderPaid
{

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_order_status_completed', [$this, 'documentCreateComplete']);
        add_action('woocommerce_order_status_processing', [$this, 'documentCreateProcessing']);
    }

    public function documentCreateComplete($orderId)
    {
        if ($this->canCreateCompleteDocument()) {
            $service = new CreateMoloniDocument($orderId);
            $orderName = $service->getOrderNumber() ?? '';

            // Translators: %1$s is the status, %2$s is the order name.
            $message = __('Automatically generating order document in status \'%1$s\' (%2$s)', 'moloni-on');
            $orderStatus = __('Complete', 'moloni-on');

            Context::logger()->info(sprintf($message, $orderStatus, $orderName), [
                'tag' => 'automatic:document:create:complete:start',
            ]);

            try {
                $service->run();

                $this->throwMessages($service);
            } catch (DocumentWarning $e) {
                $this->sendWarningEmail($orderName);

                // Translators: %1$s is the order name.
                $message = sprintf(__('There was an warning when generating the document (%1$s)', 'moloni-on'), $orderName);
                $message .= ' </br>';
                $message .= $e->getMessage();

                Notice::addmessagecustom(htmlentities($e->getError()));

                Context::logger()->alert($message, [
                        'tag' => 'automatic:document:create:complete:warning',
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            } catch (DocumentError $e) {
                $this->sendErrorEmail($orderName);

                // Translators: %1$s is the order name.
                $message = sprintf(__('There was an error when generating the document (%1$s)', 'moloni-on'), $orderName);
                $message .= ' </br>';
                $message .= wp_strip_all_tags($e->getMessage());

                Notice::addmessagecustom(htmlentities($e->getError()));

                Context::logger()->error($message, [
                        'tag' => 'automatic:document:create:complete:error',
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            } catch (Exception $ex) {
                Context::logger()->critical(__("Fatal error", 'moloni-on'), [
                    'tag' => 'automatic:document:create:complete:fatalerror',
                    'message' => $ex->getMessage()
                ]);
            }
        }
    }

    public function documentCreateProcessing($orderId)
    {
        if ($this->canCreateProcessingDocument()) {
            $service = new CreateMoloniDocument($orderId);
            $orderName = $service->getOrderNumber() ?? '';

            // Translators: %1$s is the status, %2$s is the order name.
            $message = __('Automatically generating order document in status \'%1$s\' (%2$s)', 'moloni-on');
            $orderStatus = __('Processing', 'moloni-on');

            Context::logger()->info(sprintf($message, $orderStatus, $orderName), [
                    'tag' => 'automatic:document:create:processing:start',
                ]
            );

            try {
                $service->run();

                $this->throwMessages($service);
            } catch (DocumentWarning $e) {
                $this->sendWarningEmail($orderName);

                // Translators: %1$s is the order name.
                $message = sprintf(__('There was an warning when generating the document (%1$s)', 'moloni-on'), $orderName);
                $message .= ' </br>';
                $message .= $e->getMessage();

                Notice::addmessagecustom(htmlentities($e->getError()));

                Context::logger()->alert($message, [
                        'tag' => 'automatic:document:create:processing:warning',
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            } catch (DocumentError $e) {
                $this->sendErrorEmail($orderName);

                // Translators: %1$s is the order name.
                $message = sprintf(__('There was an error when generating the document (%1$s)', 'moloni-on'), $orderName);
                $message .= ' </br>';
                $message .= wp_strip_all_tags($e->getMessage());

                Notice::addmessagecustom(htmlentities($e->getError()));

                Context::logger()->error($message, [
                        'tag' => 'automatic:document:create:processing:error',
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            } catch (Exception $ex) {
                Context::logger()->critical(__("Fatal error", 'moloni-on'), [
                    'tag' => 'automatic:document:create:processing:fatalerror',
                    'message' => $ex->getMessage()
                ]);
            }
        }
    }

    //          Privates          //

    /**
     * Verify if it can be created
     */
    private function canCreateCompleteDocument(): bool
    {
        if (!Start::login(true) || !defined("INVOICE_AUTO") || (int)INVOICE_AUTO === Boolean::NO) {
            return false;
        }

        if (!defined('INVOICE_AUTO_STATUS')) {
            return true;
        }

        return INVOICE_AUTO_STATUS === AutomaticDocumentsStatus::COMPLETED;
    }

    /**
     * Verify if it can be created
     */
    private function canCreateProcessingDocument(): bool
    {
        return Start::login(true)
            && defined("INVOICE_AUTO")
            && (int)INVOICE_AUTO === Boolean::YES
            && defined('INVOICE_AUTO_STATUS')
            && INVOICE_AUTO_STATUS === AutomaticDocumentsStatus::PROCESSING;
    }

    private function sendWarningEmail(string $orderName): void
    {
        if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
            new DocumentWarning(ALERT_EMAIL, $orderName);
        }
    }

    private function sendErrorEmail(string $orderName): void
    {
        if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
            new DocumentFailed(ALERT_EMAIL, $orderName);
        }
    }

    private function throwMessages(CreateMoloniDocument $service): void
    {
        if ($service->getDocumentId() > 0 && is_admin()) {
            $adminUrl = Context::getAdminUrl("action=getInvoice&id={$service->getDocumentId()}");

            $viewUrl = ' <a href="' . esc_url($adminUrl) . '" target="_BLANK">';
            $viewUrl .= __('View document', 'moloni-on');
            $viewUrl .= '</a>';

            add_settings_error('molonion', 'moloni-document-created-success', __('Document was created!', 'moloni-on') . $viewUrl, 'updated');
        }
    }
}
