<?php

namespace MoloniOn\Services\Imports;

use Exception;
use MoloniOn\API\Products;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\SyncLogsType;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Helpers\References;
use MoloniOn\Models\SyncLogs;
use MoloniOn\Services\WcProduct\Create\CreateChildProduct;
use MoloniOn\Services\WcProduct\Create\CreateParentProduct;
use MoloniOn\Services\WcProduct\Create\CreateSimpleProduct;

class ImportProducts extends ImportService
{
    public function run(): void
    {
        $props = [
            'options' => [
                'order' => [
                    'field' => 'reference',
                    'sort' => 'DESC',
                ],
                'pagination' => [
                    'page' => $this->page,
                    'qty' => $this->itemsPerPage,
                ]
            ]
        ];

        try {
            $query = Products::queryProducts($props);
        } catch (APIExeption $e) {
            return;
        }

        $this->totalResults = (int)($query['data']['products']['options']['pagination']['count'] ?? 0);

        $data = $query['data']['products']['data'] ?? [];

        foreach ($data as $product) {
            if (References::isIgnoredReference($product['reference'])) {
                $this->errorProducts[] = [$product['reference'] => 'Reference is blacklisted'];

                continue;
            }

            if (!empty($product['variants']) && !$this->isSyncProductWithVariantsActive()) {
                $this->errorProducts[] = [$product['reference'] => 'Synchronization of products with variants is disabled'];

                continue;
            }

            $wcProduct = $this->fetchWcProduct($product);

            if (!empty($wcProduct)) {
                $this->errorProducts[] = [$product['reference'] => 'Product already exists in WooCommerce'];

                continue;
            }

            try {
                if (empty($product['variants'])) {
                    $this->createProductSimple($product);
                } else {
                    $this->createProductWithVariations($product);
                }
            } catch (Exception $exception) {
                $this->errorProducts[] = [$product['reference'] => $exception->getMessage()];
            }
        }

        // Translators: %1$s is the part number.
        Context::logger()->info(sprintf(__('Products import. Part %1$s', 'moloni-on'), $this->page), [
                'tag' => 'tool:import:product',
                'success' => $this->syncedProducts,
                'error' => $this->errorProducts,
                'settings' => [
                    'syncProductWithVariations' => $this->isSyncProductWithVariantsActive()
                ]
            ]
        );
    }

    //              Privates              //

    private function createProductSimple(array $product)
    {
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $product['productId']);

        $service = new CreateSimpleProduct($product);
        $service->run();

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $service->getWcProduct()->get_id());

        $this->syncedProducts[] = $product['reference'];
    }

    private function createProductWithVariations(array $product)
    {
        SyncLogs::addTimeout(SyncLogsType::MOLONI_PRODUCT_SAVE, $product['productId']);

        $service = new CreateParentProduct($product);
        $service->run();

        $wcParentProduct = $service->getWcProduct();

        SyncLogs::addTimeout(SyncLogsType::WC_PRODUCT_SAVE, $wcParentProduct->get_id());

        foreach ($product['variants'] as $variant) {
            if ((int)$variant['visible'] === Boolean::NO) {
                continue;
            }

            $service = new CreateChildProduct($variant, $wcParentProduct);
            $service->run();
        }

        $this->syncedProducts[] = $product['reference'];
    }
}
