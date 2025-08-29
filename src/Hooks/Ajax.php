<?php

namespace MoloniOn\Hooks;

use Exception;
use MoloniOn\API\Companies;
use MoloniOn\API\Products;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\SyncLogsType;
use MoloniOn\Exceptions\Core\MoloniException;
use MoloniOn\Exceptions\DocumentError;
use MoloniOn\Exceptions\DocumentWarning;
use MoloniOn\Exceptions\GenericException;
use MoloniOn\Helpers\MoloniWarehouse;
use MoloniOn\Helpers\Security;
use MoloniOn\Models\SyncLogs;
use MoloniOn\Plugin;
use MoloniOn\Services\Exports\ExportProducts;
use MoloniOn\Services\Exports\ExportStockChanges;
use MoloniOn\Services\Imports\ImportProducts;
use MoloniOn\Services\Imports\ImportStockChanges;
use MoloniOn\Services\Orders\CreateMoloniDocument;
use MoloniOn\Services\Orders\DiscardOrder;
use MoloniOn\Services\WcProduct\Create\CreateChildProduct;
use MoloniOn\Services\WcProduct\Create\CreateParentProduct;
use MoloniOn\Start;

class Ajax
{
    public $parent;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('wp_ajax_molonion_gen_invoice', [$this, 'molonion_gen_invoice']);
        add_action('wp_ajax_molonion_discard_order', [$this, 'molonion_discard_order']);

        add_action('wp_ajax_molonion_tools_mass_import_stock', [$this, 'molonion_tools_mass_import_stock']);
        add_action('wp_ajax_molonion_tools_mass_import_product', [$this, 'molonion_tools_mass_import_product']);
        add_action('wp_ajax_molonion_tools_mass_export_stock', [$this, 'molonion_tools_mass_export_stock']);
        add_action('wp_ajax_molonion_tools_mass_export_product', [$this, 'molonion_tools_mass_export_product']);

        add_action('wp_ajax_molonion_tools_create_wc_product', [$this, 'molonion_tools_create_wc_product']);
        add_action('wp_ajax_molonion_tools_update_wc_stock', [$this, 'molonion_tools_update_wc_stock']);
        add_action('wp_ajax_molonion_tools_create_moloni_product', [$this, 'molonion_tools_create_moloni_product']);
        add_action('wp_ajax_molonion_tools_update_moloni_stock', [$this, 'molonion_tools_update_moloni_stock']);
    }

    //             Public's             //

    public function molonion_gen_invoice()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new CreateMoloniDocument((int)$_REQUEST['id']);
        $orderName = $service->getOrderNumber() ?? '';

        try {
            $service->run();

            // Translators: %1$s is the order name.
            $log = __('Document %1$s successfully inserted', 'moloni-on');

            $response = [
                'valid' => 1,
                'message' => sprintf($log, $service->getOrderNumber())
            ];
        } catch (DocumentWarning $e) {
            // Translators: %1$s is the order name.
            $message = sprintf(__('There was an warning when generating the document (%1$s)', 'moloni-on'), $orderName);
            $message .= ' </br>';
            $message .= $e->getMessage();

            Context::logger()->alert($message, [
                    'tag' => 'ajax:document:create:warning',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );

            $response = ['valid' => 1, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (DocumentError $e) {
            // Translators: %1$s is the order name.
            $message = sprintf(__('There was an error when generating the document (%1$s)', 'moloni-on'), $orderName);
            $message .= ' </br>';
            $message .= wp_strip_all_tags($e->getMessage());

            Context::logger()->error($message, [
                    'tag' => 'ajax:document:create:error',
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );

            $response = ['valid' => 0, 'message' => $e->getMessage(), 'data' => $e->getData()];
        } catch (Exception $e) {
            Context::logger()->critical(__("Fatal error", 'moloni-on'), [
                'tag' => 'ajax:document:create:fatalerror',
                'message' => $e->getMessage()
            ]);

            $response = ['valid' => 0, 'message' => $e->getMessage()];
        }

        $this->sendJson($response);
    }

    public function molonion_discard_order()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $response = [
            'valid' => 1
        ];

        $order = wc_get_order((int)$_REQUEST['id']);

        $service = new DiscardOrder($order);
        $service->run();
        $service->saveLog();

        $this->sendJson($response);
    }


    public function molonion_tools_mass_import_stock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function molonion_tools_mass_import_product()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ImportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function molonion_tools_mass_export_stock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportStockChanges((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }

    public function molonion_tools_mass_export_product()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $service = new ExportProducts((int)$_REQUEST['page']);
        $service->run();

        $response = [
            'valid' => 1,
            'overlayContent' => '',
            'hasMore' => $service->getHasMore(),
            'totalResults' => $service->getTotalResults(),
            'currentPercentage' => $service->getCurrentPercentage()
        ];

        $response['overlayContent'] = $this->loadModalContent($response);

        $this->sendJson($response);
    }


    public function molonion_tools_create_wc_product()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'action' => 'toolsCreateWcProduct'
            ]
        ];

        try {
            $mlProduct = Products::queryProduct(['productId' => $mlProductId])['data']['product']['data'] ?? [];

            if (empty($mlProduct)) {
                throw new GenericException(__('Product not found in Moloni account', 'moloni-on'));
            }

            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $mlProductId);

            if (empty($mlProduct['variants'])) {
                $service = new \MoloniOn\Services\WcProduct\Create\CreateSimpleProduct($mlProduct);
                $service->run();
                $service->saveLog();
            } else {
                $service = new CreateParentProduct($mlProduct);
                $service->run();
                $service->saveLog();

                $wcParentProduct = $service->getWcProduct();

                foreach ($mlProduct['variants'] as $variant) {
                    if ((int)$variant['visible'] === Boolean::NO) {
                        continue;
                    }

                    $service = new CreateChildProduct($variant, $wcParentProduct);
                    $service->run();
                    $service->saveLog();
                }
            }

            $warehouseId = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1;
            $company = Companies::queryCompany()['data']['company']['data'] ?? [];

            $checkService = new \MoloniOn\Services\MoloniProduct\Page\CheckProduct($mlProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function molonion_tools_update_wc_stock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'wc_product_id' => $wcProductId,
                'action' => 'toolsUpdateWcStock'
            ]
        ];

        try {
            $mlProduct = Products::queryProduct(['productId' => $mlProductId])['data']['product']['data'] ?? [];

            if (empty($mlProduct)) {
                throw new GenericException(__('Product not found in Moloni account', 'moloni-on'));
            }

            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException(__('Product not found in WooCommerce store', 'moloni-on'));
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductId);
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $mlProductId);

            $service = new \MoloniOn\Services\WcProduct\Stock\SyncProductStock($mlProduct, $wcProduct);
            $service->run();
            $service->saveLog();

            $warehouseId = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1;
            $company = Companies::queryCompany()['data']['company']['data'] ?? [];

            $checkService = new \MoloniOn\Services\MoloniProduct\Page\CheckProduct($mlProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function molonion_tools_create_moloni_product()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'wc_product_id' => $wcProductId,
                'action' => 'toolsCreateMoloniProduct'
            ]
        ];

        $wcProduct = wc_get_product($wcProductId);

        try {
            if (empty($wcProduct)) {
                throw new GenericException(__('Product not found in WooCommerce store', 'moloni-on'));
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProductId);

            if ($wcProduct->is_type('variable') && $wcProduct->has_child()) {
                $service = new \MoloniOn\Services\MoloniProduct\Create\CreateVariantProduct($wcProduct);
            } else {
                $service = new \MoloniOn\Services\MoloniProduct\Create\CreateSimpleProduct($wcProduct);
            }

            $service->run();
            $service->saveLog();

            $company = Companies::queryCompany()['data']['company']['data'] ?? [];
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

            if (empty($warehouseId)) {
                $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
            }

            $checkService = new \MoloniOn\Services\WcProduct\Page\CheckProduct($wcProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    public function molonion_tools_update_moloni_stock()
    {
        if (!$this->isAuthed()) {
            return;
        }

        $mlProductId = (int)($_POST['ml_product_id'] ?? 0);
        $wcProductId = (int)($_POST['wc_product_id'] ?? 0);
        $response = [
            'valid' => 1,
            'message' => '',
            'product_row' => '',
            'post' => [
                'ml_product_id' => $mlProductId,
                'wc_product_id' => $wcProductId,
                'action' => 'toolsUpdateMoloniStock'
            ]
        ];

        try {

            $wcProduct = wc_get_product($wcProductId);

            if (empty($wcProduct)) {
                throw new GenericException(__('Product not found in WooCommerce store', 'moloni-on'));
            }

            $mlProduct = Products::queryProduct(['productId' => $mlProductId])['data']['product']['data'] ?? [];

            if (empty($mlProduct)) {
                throw new GenericException(__('Product not found in Moloni account', 'moloni-on'));
            }

            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProductId);
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $mlProductId);

            $service = new \MoloniOn\Services\MoloniProduct\Stock\SyncProductStock($wcProduct, $mlProduct);
            $service->run();
            $service->saveLog();

            $company = Companies::queryCompany()['data']['company']['data'] ?? [];
            $warehouseId = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0;

            if (empty($warehouseId)) {
                $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
            }

            $checkService = new \MoloniOn\Services\WcProduct\Page\CheckProduct($wcProduct, $warehouseId, $company);
            $checkService->run();

            $response['product_row'] = $checkService->getRowsHtml();
        } catch (MoloniException $e) {
            $response['valid'] = 0;
            $response['message'] = $e->getMessage();
        }

        $this->sendJson($response);
    }

    //             Privates             //

    private function isAuthed(): bool
    {
        Security::verify_ajax_request_or_die();

        if (!current_user_can('manage_woocommerce')) {
            return false;
        }

        return Start::login(true);
    }

    /**
     * Load tools modal content
     *
     * @see https://wpadmin.bracketspace.com/
     */
    private function loadModalContent($data)
    {
        ob_start();

        include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/Blocks/ActionModalContent.php';

        return ob_get_clean();
    }

    /**
     * Return and stop execution afterward.
     *
     * @see https://developer.wordpress.org/reference/hooks/wp_ajax_action/
     *
     * @param array $data
     * @return void
     */
    private function sendJson(array $data)
    {
        wp_send_json($data);
        wp_die();
    }
}
