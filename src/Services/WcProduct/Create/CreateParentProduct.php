<?php

namespace MoloniOn\Services\WcProduct\Create;

use MoloniOn\Context;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use WC_Product_Variable;

class CreateParentProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct)
    {
        $this->moloniProduct = $moloniProduct;
        $this->wcProduct = new WC_Product_Variable();
    }

    //            Publics            //

    /**
     * Runner
     */
    public function run()
    {
        $this->setName();
        $this->setReference();
        $this->setStock();

        if ($this->productShouldSyncPrice()) {
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

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }

        $this->setAttributes();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        // Translators: %1$s is the product SKU.
        $message = sprintf(__('Product with variations created in WooCommerce (%s)', 'moloni-on'), $this->wcProduct->get_sku());

        Context::logger()->info($message, [
            'tag' => 'service:wcproduct:parent:create',
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0
        ]);
    }

    //            Privates            //

    protected function setStock()
    {
        /** Stock is managed in variations level */
        $this->wcProduct->set_manage_stock(false);
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
