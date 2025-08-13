<?php

namespace MoloniOn\Services\WcProduct\Create;

use MoloniOn\Context;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use WC_Product;

class CreateSimpleProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product();
    }

    //            Publics            //

    public function run()
    {
        $this->setName();
        $this->setReference();

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
            $this->setTaxes();
        }

        if ($this->productShouldSyncCategories()) {
            $this->setCategories();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setDescripton();
        }

        if ($this->productShouldSyncVisibility()) {
            $this->setVisibility();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
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
        // Translators: %1$s is the product SKU.
        $message = sprintf(__('Simple product created in WooCommerce (%1$s)', 'moloni-on'), $this->wcProduct->get_sku());

        Context::logger()->info($message, [
            'tag' => 'service:wcproduct:simple:create',
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}
