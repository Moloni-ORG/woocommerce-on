<?php

namespace MoloniOn\Hooks;

use Exception;
use MoloniOn\API\Products;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\SyncLogsType;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\Core\MoloniException;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Exceptions\HookException;
use MoloniOn\Exceptions\ServiceException;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Models\SyncLogs;
use MoloniOn\Notice;
use MoloniOn\Plugin;
use MoloniOn\Services\MoloniProduct\Create\CreateSimpleProduct;
use MoloniOn\Services\MoloniProduct\Create\CreateVariantProduct;
use MoloniOn\Services\MoloniProduct\Helpers\Variants\FindVariantByProperties;
use MoloniOn\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniOn\Services\MoloniProduct\Stock\SyncProductStock;
use MoloniOn\Services\MoloniProduct\Update\UpdateSimpleProduct;
use MoloniOn\Services\MoloniProduct\Update\UpdateVariantProduct;
use MoloniOn\Start;
use MoloniOn\Traits\SettingsTrait;
use WC_Product;

class ProductUpdate
{
    use SettingsTrait;

    /**
     * Main class
     *
     * @var Plugin
     */
    public $parent;

    /**
     * WooCommerce product ID
     *
     * @var int|null
     */
    private $wcProductId = 0;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('woocommerce_update_product', [$this, 'productSave']);
    }

    public function productSave($wcProductId)
    {
        global $post;

        /** Documents status change is triggering this... */
        if (empty($post) || $post->post_type !== 'product') {
            return;
        }

        /** WooCommerce product has some type of timeout */
        if (SyncLogs::hasTimeout([SyncLogsType::WC_PRODUCT_SAVE, SyncLogsType::WC_PRODUCT_STOCK], $wcProductId)) {
            return;
        }

        /** Login is valid */
        if (!(new Start())->isFullyAuthed()) {
            return;
        }

        /** No synchronization is active */
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $this->wcProductId = $wcProductId;

        $wcProduct = $this->fetchWcProduct($wcProductId);

        try {
            $this->validateWcProduct($wcProduct);

            if ($wcProduct->is_type('variable')) {
                if ($this->isSyncProductWithVariantsActive()) {
                    $moloniProduct = $this->fetchMoloniProduct($wcProduct);

                    if (empty($moloniProduct)) {
                        $this->createVariant($wcProduct);
                    } else {
                        $this->updateVariant($wcProduct, $moloniProduct);
                    }
                } else {
                    $childIds = $wcProduct->get_children();

                    foreach ($childIds as $childId) {
                        $wcVariation = $this->fetchWcProduct($childId);

                        if (!$this->wcVariationIsValid($wcVariation)) {
                            continue;
                        }

                        $moloniProduct = $this->fetchMoloniProduct($wcVariation);

                        if (empty($moloniProduct)) {
                            $this->createSimple($wcVariation);
                        } else {
                            $this->updateSimple($wcVariation, $moloniProduct);
                        }
                    }
                }
            } else {
                $moloniProduct = $this->fetchMoloniProduct($wcProduct);

                if (empty($moloniProduct)) {
                    $this->createSimple($wcProduct);
                } else {
                    $this->updateSimple($wcProduct, $moloniProduct);
                }
            }
        } catch (MoloniException $e) {
            Notice::addmessagecustom(htmlentities($e->geterror()));

            $message = __('Error synchronizing products to Moloni.', 'moloni-on');
            $message .= ' </br>';
            $message .= $e->getMessage();

            if (!in_array(substr($message, -1), ['.', '!', '?'])) {
                $message .= '.';
            }

            Context::logger()->error($message, [
                'tag' => 'automatic:product:save:error',
                'message' => $e->getMessage(),
                'extra' => [
                    'wcProductId' => $wcProductId,
                    'data' => $e->getData(),
                ]
            ]);
        } catch (Exception $e) {
            Context::logger()->critical(__('Fatal error', 'moloni-on'), [
                'tag' => 'automatic:product:save:fatalerror',
                'message' => $e->getMessage(),
                'extra' => [
                    'wcProductId' => $wcProductId,
                ]
            ]);
        }
    }

    //          Actions          //

    /**
     * Creator action
     *
     * @throws ServiceException
     */
    private function createSimple(WC_Product $wcProduct)
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());

        $service = new CreateSimpleProduct($wcProduct);
        $service->run();
        $service->saveLog();
    }

    /**
     * Updater action
     *
     * @throws ServiceException
     * @throws HookException
     */
    private function updateSimple(WC_Product $wcProduct, array $moloniProduct)
    {
        if (!empty($moloniProduct['variants']) && $moloniProduct['deletable'] === false) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new HookException(__('Product types do not match', 'moloni-on'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId'])) {
            return;
        }

        if ($this->shouldSyncProduct()) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

            $service = new UpdateSimpleProduct($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();

            $moloniProduct = $service->getMoloniProduct();
        }

        if ($this->shouldSyncStock() && $wcProduct->managing_stock() && (int)$moloniProduct['hasStock'] === Boolean::YES) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $moloniProduct['productId']);

            $service = new SyncProductStock($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();
        }
    }

    /**
     * Creator action
     *
     * @throws ServiceException
     */
    private function createVariant(WC_Product $wcProduct)
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());

        $service = new CreateVariantProduct($wcProduct);
        $service->run();
        $service->saveLog();
    }

    /**
     * Updater action
     *
     * @throws ServiceException
     * @throws HookException
     * @throws HelperException
     */
    private function updateVariant(WC_Product $wcProduct, array $moloniProduct)
    {
        if (empty($moloniProduct['variants']) && $moloniProduct['deletable'] === false) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new HookException(__('Product types do not match', 'moloni-on'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId'])) {
            return;
        }

        if ($this->shouldSyncProduct()) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $moloniProduct['productId']);

            $service = new UpdateVariantProduct($wcProduct, $moloniProduct);
            $service->run();
            $service->saveLog();

            $moloniProduct = $service->getMoloniProduct();
        }

        if ($this->shouldSyncStock() && (int)$moloniProduct['hasStock'] === Boolean::YES) {
            SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
            SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $moloniProduct['productId']);

            $wcProductAttributes = (new ParseProductProperties($wcProduct))->handle();
            $childIds = $wcProduct->get_children();

            foreach ($childIds as $childId) {
                $moloniVariant = [];
                $wcVariation = wc_get_product($childId);

                if (!$wcVariation->managing_stock()) {
                    continue;
                }

                $association = ProductAssociations::findByWcId($wcVariation->get_id());

                if (!empty($association)) {
                    foreach ($moloniProduct['variants'] as $variant) {
                        if ((int)$variant['productId'] === (int)$association['ml_product_id']) {
                            $moloniVariant = $variant;

                            break;
                        }
                    }
                }

                if (empty($moloniVariant)) {
                    $wcTargetProductAttributes = $wcProductAttributes[$wcVariation->get_id()] ?? [];
                    $moloniVariant = (new FindVariantByProperties($wcTargetProductAttributes, $moloniProduct))->handle();
                }

                if (empty($moloniVariant)) {
                    continue;
                }

                SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcVariation->get_id());

                $service = new SyncProductStock($wcVariation, $moloniVariant);
                $service->run();
                $service->saveLog();
            }
        }
    }

    //          Privatess          //

    /**
     * Fetch WooCommerce product
     */
    private function fetchWcProduct($wcProductId): WC_Product
    {
        return wc_get_product($wcProductId);
    }

    /**
     * Fetch Moloni product
     *
     * @throws APIExeption
     */
    private function fetchMoloniProduct(WC_Product $wcProduct): array
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                return $byId;
            }

            ProductAssociations::deleteById($association['id']);
        }

        if (empty($wcProduct->get_sku())) {
            return [];
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $wcProduct->get_sku(),
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'in',
                        'value' => '[0, 1]'
                    ]
                ]
            ]
        ];

        $query = Products::queryProducts($variables);

        $byReference = $query['data']['products']['data'] ?? [];

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            return $byReference[0];
        }

        return [];
    }

    //          Auxiliary          //

    private function shouldSyncProduct(): bool
    {
        return Context::settings()->getInt('moloni_product_sync') === Boolean::YES;
    }

    private function shouldSyncStock(): bool
    {
        if (Context::settings()->getInt('moloni_stock_sync') !== Boolean::YES) {
            return false;
        }

        return Context::company()->canSyncStock();
    }

    //          Validations          //

    /**
     * Validate WooCommerce product
     *
     * @throws HookException
     */
    private function validateWcProduct(?WC_Product $wcProduct)
    {
        if (empty($wcProduct)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new HookException(__('Product not found', 'moloni-on'));
        }

        if ($wcProduct->get_status() === 'draft') {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new HookException(__('Product is not published', 'moloni-on'));
        }

        if (empty($wcProduct->get_sku())) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new HookException(__('Product does not have reference', 'moloni-on'));
        }
    }

    /**
     * Validate WooCommerce variation
     */
    private function wcVariationIsValid(?WC_Product $wcProduct): bool
    {
        if (empty($wcProduct)) {
            return false;
        }

        if ($wcProduct->get_status() === 'draft') {
            return false;
        }

        if (empty($wcProduct->get_sku())) {
            return false;
        }

        if (SyncLogs::hasTimeout([SyncLogsType::WC_PRODUCT_SAVE, SyncLogsType::WC_PRODUCT_STOCK], $wcProduct->get_id())) {
            return false;
        }

        return true;
    }
}
