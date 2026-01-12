<?php

namespace MoloniOn\Services\WcProduct\Page;

use MoloniOn\Context;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Helpers\MoloniWarehouse;

class FetchAndCheckProducts
{
    private static $perPage = 20;

    private $page = 1;
    private $filters = [];

    private $rows = [];

    private $products = [];
    private $totalProducts = 0;

    private $warehouseId = 0;

    //            Public's            //

    /**
     * Service runner
     *
     * @return void
     *
     * @throws HelperException
     */
    public function run()
    {
        $this
            ->loadWarehouse()
            ->fetchProducts();

        foreach ($this->products as $product) {
            $service = new CheckProduct($product, $this->warehouseId);
            $service->run();

            $this->rows[] = $service->getRowsHtml();
        }
    }

    public function getPaginator()
    {
        $baseArguments = add_query_arg([
            'paged' => '%#%',
            'filter_name' => $this->filters['filter_name'],
            'filter_reference' => $this->filters['filter_reference'],
        ]);

        $args = [
            'base' => $baseArguments,
            'format' => '',
            'current' => $this->page,
            'total' => ceil($this->totalProducts / self::$perPage),
        ];

        return paginate_links($args) ?? '';
    }

    //            Privates            //

    /**
     * Load warehouse to use
     *
     * @throws HelperException
     */
    private function loadWarehouse(): FetchAndCheckProducts
    {
        if (!Context::company()->canSyncStock()) {
            return $this;
        }

        $warehouseId = Context::settings()->getInt('moloni_stock_sync_warehouse');

        if (empty($warehouseId)) {
            $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
        }

        $this->warehouseId = $warehouseId;

        return $this;
    }

    //            Gets            //

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTotalProducts(): int
    {
        return $this->totalProducts;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    //            Sets            //

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    //            Requests            //

    /**
     * Fetch products from WooCommerce
     */
    private function fetchProducts()
    {
        /**
         * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
         */
        $filters = [
            'status' => ['publish'],
            'limit' => self::$perPage,
            'page' => $this->page,
            'paginate' => true,
            'orderby' => [
                'ID' => 'DESC',
            ],
        ];

        if (!empty($this->filters['filter_reference'])) {
            $filters['sku'] = $this->filters['filter_reference'];
        }

        if (!empty($this->filters['filter_name'])) {
            $filters['name'] = $this->filters['filter_name'];
        }

        $query = wc_get_products($filters);

        $this->products = $query->products ?? [];
        $this->totalProducts = (int)($query->total ?? 0);
    }
}
