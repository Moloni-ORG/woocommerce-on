<?php

namespace MoloniOn\Services\WcProduct\Update;

use MoloniOn\Context;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use WC_Product;

class UpdateSimpleProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = $wcProduct;
    }

    public function run()
    {
        if ($this->productShouldSyncName()) {
            $this->setName();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setDescripton();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
            $this->setTaxes();
        }

        if ($this->productShouldSyncVisibility()) {
            $this->setVisibility();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
        }

        if ($this->productShouldSyncCategories()) {
            $this->setCategories();
        }

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        // Translators: %s is the WooCommerce product SKU.
        $message = sprintf(__('Simple product updated in WooCommerce (%s)', 'moloni-on'), $this->wcProduct->get_sku());

        Context::logger()->info($message, [
            'tag' => 'service:wcproduct:simple:update',
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::deleteByWcId($this->wcProduct->get_id());
        ProductAssociations::deleteByMoloniId($this->moloniProduct['productId']);

        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}
