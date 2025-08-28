<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\WebHooks;

use Exception;
use MoloniOn\API\Products as ApiProducts;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\SyncLogsType;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\Core\MoloniException;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Exceptions\WebhookException;
use MoloniOn\Helpers\References;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Models\SyncLogs;
use MoloniOn\Services\MoloniProduct\Helpers\Variants\ParseProductProperties;
use MoloniOn\Services\WcProduct\Create\CreateChildProduct;
use MoloniOn\Services\WcProduct\Create\CreateParentProduct;
use MoloniOn\Services\WcProduct\Create\CreateSimpleProduct;
use MoloniOn\Services\WcProduct\Helpers\Variations\FindVariation;
use MoloniOn\Services\WcProduct\Stock\SyncProductStock;
use MoloniOn\Services\WcProduct\Update\UpdateChildProduct;
use MoloniOn\Services\WcProduct\Update\UpdateParentProduct;
use MoloniOn\Services\WcProduct\Update\UpdateSimpleProduct;
use MoloniOn\Start;
use MoloniOn\Traits\SettingsTrait;
use WP_REST_Request;

class Products
{
    use SettingsTrait;

    /**
     * Moloni product
     *
     * @var array
     */
    private $moloniProduct = [];

    /**
     * Products constructor.
     */
    public function __construct()
    {
        $namespace = Context::configs()->get('rest_api');

        //create a new route
        register_rest_route($namespace, 'products/(?P<hash>[a-f0-9]{32}$)', [
            'methods' => 'POST',
            'callback' => [$this, 'products'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Handles data form WebHook
     *
     * @param $requestData
     *
     * @return void
     */
    public function products($requestData)
    {
        $parameters = $requestData->get_params();

        try {
            /** Model has to be 'Product', needs to be logged in and received hash has to match logged in company id hash */
            if ($parameters['model'] !== 'Product' || !Start::login(true) || !$this->checkHash($parameters['hash'])) {
                return;
            }

            $productId = (int)sanitize_text_field($parameters['productId']);

            $this->fetchMoloniProduct($productId);

            //switch between operations
            switch ($parameters['operation']) {
                case 'create':
                    $this->onCreate();
                    break;
                case 'update':
                    $this->onUpdate();
                    break;
                case 'stockChanged':
                    $this->onStockUpdate();
                    break;
            }

            $this->reply();
        } catch (MoloniException $exception) {
            $message = __('Error synchronizing products to WooCommerce.', 'moloni-on');
            $message .= ' </br>';
            $message .= $exception->getMessage();

            if (!in_array(substr($message, -1), ['.', '!', '?'])) {
                $message .= '.';
            }

            Context::logger()->error($message, [
                'tag' => 'automatic:product:save:error',
                'message' => $exception->getMessage(),
                'extra' => [
                    'parameters' => $parameters,
                    'data' => $exception->getData(),
                ]
            ]);

            $this->reply(0, $exception->getMessage());
        } catch (Exception $exception) {
            Context::logger()->critical(__('Fatal error', 'moloni-on'), [
                    'tag' => 'webhook:product:fatalerror',
                    'message' => $exception->getMessage(),
                    'extra' => [
                        'parameters' => $parameters,
                    ]
                ]
            );

            $this->reply(0, $exception->getMessage());
        }
    }

    //            Actions            //

    /**
     * Create action
     *
     * @throws WebhookException
     */
    private function onCreate()
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $this->validateMoloniProduct();

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (!empty($wcProduct)) {
            throw new WebhookException(__('Product already exists', 'moloni-on'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId'])) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId']);

        if ($this->moloniProductHasVariants()) {
            $service = new CreateParentProduct($this->moloniProduct);
            $service->run();
            $service->saveLog();

            $wcParentProduct = $service->getWcProduct();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO) {
                    continue;
                }

                $service = new CreateChildProduct($variant, $wcParentProduct);
                $service->run();
                $service->saveLog();
            }
        } else {
            $service = new CreateSimpleProduct($this->moloniProduct);
            $service->run();
            $service->saveLog();
        }
    }

    /**
     * On Moloni product update action
     *
     * @throws HelperException|WebhookException
     */
    private function onUpdate()
    {
        if (!$this->shouldSyncProduct()) {
            return;
        }

        $this->validateMoloniProduct();

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (empty($wcProduct)) {
            throw new WebhookException(__('Product not found', 'moloni-on'));
        }

        /** Both need to be the same kind */
        if ($this->moloniProductHasVariants() !== $wcProduct->is_type('variable')) {
            throw new WebhookException(__('Product types do not match', 'moloni-on'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id()) ||
            SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId'])) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $this->moloniProduct['productId']);

        if ($this->moloniProductHasVariants()) {
            $service = new UpdateParentProduct($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();

            $wcParentAttributes = (new ParseProductProperties($wcProduct))->handle();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO) {
                    continue;
                }

                $wcProductVariation = (new FindVariation($wcParentAttributes, $variant))->run();

                if (empty($wcProductVariation)) {
                    $service = new CreateChildProduct($variant, $wcProduct);
                } else {
                    $service = new UpdateChildProduct($variant, $wcProductVariation, $wcProduct);
                }

                $service->run();
                $service->saveLog();
            }
        } else {
            $service = new UpdateSimpleProduct($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();
        }
    }

    /**
     * On Moloni stock update
     *
     * @throws HelperException|WebhookException
     */
    private function onStockUpdate()
    {
        if (!$this->shouldSyncStock()) {
            return;
        }

        $this->validateMoloniProduct();

        $wcProduct = $this->fetchWcProduct($this->moloniProduct);

        if (empty($wcProduct)) {
            throw new WebhookException(__('Product not found', 'moloni-on'));
        }

        if (SyncLogs::hasTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id()) ||
            SyncLogs::hasTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $this->moloniProduct['productId'])) {
            return;
        }

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_STOCK, $wcProduct->get_id());
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_STOCK, $this->moloniProduct['productId']);

        /** Both need to be the same kind */
        if ($this->moloniProductHasVariants() !== $wcProduct->is_type('variable')) {
            throw new WebhookException(__('Product types do not match', 'moloni-on'));
        }

        if ($this->moloniProductHasVariants()) {

            $wcParentAttributes = (new ParseProductProperties($wcProduct))->handle();

            foreach ($this->moloniProduct['variants'] as $variant) {
                if ((int)$variant['visible'] === Boolean::NO || (int)$variant['hasStock'] === Boolean::NO) {
                    continue;
                }

                $wcProductVariation = (new FindVariation($wcParentAttributes, $variant))->run();

                if (empty($wcProductVariation) || !$wcProductVariation->managing_stock()) {
                    continue;
                }

                $service = new SyncProductStock($variant, $wcProductVariation);
                $service->run();
                $service->saveLog();
            }
        } else {
            if ((int)$this->moloniProduct['hasStock'] === Boolean::NO || !$wcProduct->managing_stock()) {
                throw new WebhookException(__('Product does not manage stock', 'moloni-on'));
            }

            $service = new SyncProductStock($this->moloniProduct, $wcProduct);
            $service->run();
            $service->saveLog();
        }
    }

    //            Privates            //

    private function reply(?int $valid = 1, ?string $message = ''): void
    {
        echo wp_json_encode(['valid' => $valid, 'message' => $message]);
    }

    //            Auxiliary            //

    /**
     * Fetch Moloni product
     *
     * @throws WebhookException
     */
    private function fetchMoloniProduct(int $productId)
    {
        try {
            $query = ApiProducts::queryProduct([
                'productId' => $productId
            ]);

            $moloniProduct = $query['data']['product']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new WebhookException($e->getMessage());
        }

        $this->moloniProduct = $moloniProduct;
    }

    private function fetchWcProduct(array $moloniProduct)
    {
        /** Fetch by our associaitons table */

        $association = ProductAssociations::findByMoloniId($moloniProduct['productId']);

        if (!empty($association)) {
            $wcProduct = wc_get_product($association['wc_product_id']);

            if (!empty($wcProduct)) {
                return $wcProduct;
            }

            ProductAssociations::deleteById($association['id']);
        }

        /** Fetch by reference */

        $wcProductId = wc_get_product_id_by_sku($moloniProduct['reference']);

        if ($wcProductId > 0) {
            return wc_get_product($wcProductId);
        }

        return null;
    }

    /**
     * Checks if hash with company id hash
     *
     * @param string $hash
     *
     * @return bool
     */
    private function checkHash(string $hash): bool
    {
        return hash('md5', Context::$MOLONI_ON_COMPANY_ID) === $hash;
    }

    //            Verifications            //

    private function shouldSyncProduct(): bool
    {
        return defined('HOOK_PRODUCT_SYNC') && (int)HOOK_PRODUCT_SYNC === Boolean::YES;
    }

    private function shouldSyncStock(): bool
    {
        return defined('HOOK_STOCK_SYNC') && (int)HOOK_STOCK_SYNC === Boolean::YES;
    }

    private function moloniProductHasVariants(): bool
    {
        return !empty($this->moloniProduct['variants']);
    }

    /**
     * Validate Moloni product data
     *
     * @throws WebhookException
     */
    private function validateMoloniProduct()
    {
        /** Product not found */
        if (empty($this->moloniProduct)) {
            throw new WebhookException(__('Moloni product not found', 'moloni-on'));
        }

        /** We only want to update the main product */
        if ($this->moloniProduct['parent'] !== null) {
            throw new WebhookException(__('Product is variant, will be skipped', 'moloni-on'));
        }

        /** Do not sync shipping product */
        if (References::isIgnoredReference($this->moloniProduct['reference'])) {
            throw new WebhookException(__('Product reference blacklisted', 'moloni-on'));
        }

        /** Do not sync product with varianst if settings is not set */
        if ($this->moloniProductHasVariants() &&
            (!defined('SYNC_PRODUCTS_WITH_VARIANTS') || (int)SYNC_PRODUCTS_WITH_VARIANTS === Boolean::NO)) {
            throw new WebhookException(__('Synchronization of products with variants is disabled', 'moloni-on'));
        }
    }
}
