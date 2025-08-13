<?php

namespace MoloniOn\Services\WcProduct\Stock;

use WC_Product;
use MoloniOn\Context;
use MoloniOn\Helpers\MoloniProduct;
use MoloniOn\Services\WcProduct\Abstracts\WcStockSyncAbstract;

class SyncProductStock extends WcStockSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = $wcProduct;
    }

    public function run()
    {
        $wcStock = (int)$this->wcProduct->get_stock_quantity();
        $moloniStock = (int)MoloniProduct::parseMoloniStock(
            $this->moloniProduct,
            defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1
        );

        if ($wcStock === $moloniStock)
        {
            $msg = sprintf(
                // Translators: %1$s is the product reference.
                __('Stock is already updated in WooCommerce (%1$s)', 'moloni-on'),
                $this->moloniProduct['reference']
            );
        } else {
            $msg = sprintf(
                // Translators: %1$s is the old stock, %2$s is the new stock, %3$s is the product reference.
                __('Stock updated in WooCommerce (old: %1$s | new: %2$s) (%3$s)', 'moloni-on'),
                $wcStock,
                $moloniStock,
                $this->moloniProduct['reference']
            );

            wc_update_product_stock($this->wcProduct, $moloniStock);
        }

        $this->resultMsg = $msg;
        $this->resultData = [
            'tag' => 'service:wcproduct:sync:stock',
            'WooCommerceId' => $this->wcProduct->get_id(),
            'WooCommerceParentId' => $this->wcProduct->get_parent_id(),
            'WooCommerceStock' => $wcStock,
            'MoloniStock' => $moloniStock,
            'MoloniProductId' => $this->moloniProduct['productId'],
            'MoloniProductParentId' => $this->moloniProduct['parent']['productId'] ?? null,
            'MoloniReference' => $this->moloniProduct['reference'],
        ];
    }

    public function saveLog()
    {
        Context::logger()->info($this->resultMsg, $this->resultData);
    }
}
