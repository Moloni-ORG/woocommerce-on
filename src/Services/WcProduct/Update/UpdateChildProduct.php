<?php

namespace MoloniOn\Services\WcProduct\Update;

use MoloniOn\Context;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\WcProduct\Abstracts\WcProductSyncAbstract;
use WC_Product;

class UpdateChildProduct extends WcProductSyncAbstract
{
    public function __construct(array $moloniProduct, WC_Product $wcProduct, WC_Product $wcProductParent)
    {
        $this->moloniProduct = $moloniProduct;

        $this->wcProduct = $wcProduct;
        $this->wcProductParent = $wcProductParent;
    }

    public function run()
    {
        $this->setParent();

        if ($this->productShouldSyncName()) {
            $this->setName();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setDescripton();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
        }

        if ($this->productShouldSyncImage()) {
            $this->setImage();
        }

        $this->setVariationOptions();

        $this->wcProduct->save();

        $this->createAssociation();
    }

    public function saveLog()
    {
        $message = sprintf(__('Variation product updated in WooCommerce (%s)', 'moloni_on'), $this->wcProduct->get_sku());

        Context::logger()->info($message, [
            'tag' => 'service:wcproduct:child:update',
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => $this->moloniProduct['parent']['productId'],
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => $this->wcProduct->get_parent_id(),
        ]);
    }

    //            Auxliary            //

    protected function createAssociation()
    {
        ProductAssociations::deleteByWcId($this->wcProduct->get_id());
        ProductAssociations::deleteByMoloniId($this->moloniProduct['productId']);

        ProductAssociations::add(
            $this->wcProduct->get_id(),
            $this->wcProduct->get_parent_id(),
            $this->moloniProduct['productId'],
            $this->moloniProduct['parent']['productId']
        );
    }
}
