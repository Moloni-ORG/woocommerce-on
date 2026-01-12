<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Services\MoloniProduct\Stock;

use MoloniOn\Helpers\MoloniProduct;
use WC_Product;
use MoloniOn\API\Stocks;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Exceptions\ServiceException;
use MoloniOn\Helpers\MoloniWarehouse;
use MoloniOn\Services\MoloniProduct\Abstracts\MoloniStockSyncAbstract;
use MoloniOn\Context;

class SyncProductStock extends MoloniStockSyncAbstract
{
    public function __construct(WC_Product $wcProduct, array $moloniProduct)
    {
        $this->wcProduct = $wcProduct;
        $this->moloniProduct = $moloniProduct;
    }

    //            Publics            //

    /**
     * Runner
     *
     * @throws ServiceException
     */
    public function run()
    {
        $warehouseId = Context::settings()->getInt('moloni_stock_sync_warehouse');

        if (empty($warehouseId)) {
            try {
                $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
            } catch (HelperException $e) {
                throw new ServiceException($e->getMessage(), $e->getData());
            }
        }

        $wcStock = (int)$this->wcProduct->get_stock_quantity();
        $moloniStock = (int)MoloniProduct::parseMoloniStock($this->moloniProduct, $warehouseId);

        if ($wcStock === $moloniStock) {
            // Translators: %1$s is the product SKU.
            $msg = sprintf(__('Stock is already updated in Moloni (%1$s)', 'moloni-on'),
                $this->moloniProduct['reference']
            );
        } else {
            // Translators: %1$s is the old Moloni stock, %2$s is the new WooCommerce stock, %3$s is the product SKU.
            $msg = sprintf(__('Stock updated in Moloni (old: %1$s | new: %2$s) (%3$s)', 'moloni-on'),
                $moloniStock,
                $wcStock,
                $this->moloniProduct['reference']
            );

            $props = [
                'productId' => $this->moloniProduct['productId'],
                'notes' => 'Wordpress',
                'warehouseId' => $warehouseId,
            ];

            if ($moloniStock > $wcStock) {
                $diference = $moloniStock - $wcStock;

                $props['qty'] = $diference;

                try {
                    $mutation = Stocks::mutationStockMovementManualExitCreate(['data' => $props]);
                } catch (APIExeption $e) {
                    throw new ServiceException(
                        sprintf(
                            // Translators: %1$s is the product SKU.
                            __('Something went wrong updating stock (%1$s)', 'moloni-on'),
                            $this->moloniProduct['reference']
                        ),
                        [
                            'message' => $e->getMessage(),
                            'data' => $e->getData(),
                            'props' => $props,
                        ]
                    );
                }

                $movementId = $mutation['data']['stockMovementManualExitCreate']['data']['stockMovementId'] ?? 0;
            } else {
                $diference = $wcStock - $moloniStock;

                $props['qty'] = $diference;

                try {
                    $mutation = Stocks::mutationStockMovementManualEntryCreate(['data' => $props]);
                } catch (APIExeption $e) {

                    throw new ServiceException(
                        sprintf(
                            // Translators: %1$s is the product SKU.
                            __('Something went wrong updating stock (%1$s)', 'moloni-on'),
                            $this->moloniProduct['reference']
                        ),
                        [
                            'message' => $e->getMessage(),
                            'data' => $e->getData(),
                            'props' => $props,
                        ]
                    );
                }

                $movementId = $mutation['data']['stockMovementManualEntryCreate']['data']['stockMovementId'] ?? 0;
            }

            if (empty($movementId)) {
                throw new ServiceException(sprintf(
                    // Translators: %1$s is the product SKU.
                    __('Something went wrong updating stock (%1$s)', 'moloni-on'),
                    $this->moloniProduct['reference']
                ), [
                    'mutation' => $mutation,
                    'props' => $props
                ]);
            }
        }

        $this->resultMsg = $msg;
        $this->resultData = [
            'tag' => 'service:mlproduct:sync:stock',
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
