<?php

namespace MoloniOn\Services\MoloniProduct\Update;

use MoloniOn\Context;
use MoloniOn\Exceptions\ServiceException;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;
use WC_Product;

class UpdateSimpleProduct extends MoloniProductSyncAbstract
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
        $this->setProductId();

        if ($this->productShouldSyncName()) {
            $this->setName();
        }

        if ($this->productShouldSyncPrice()) {
            $this->setPrice();
            $this->setTaxes();
        }

        if ($this->productShouldSyncCategories()) {
            $this->setCategory();
        }

        if ($this->productShouldSyncDescription()) {
            $this->setSummary();
            $this->setNotes();
        }

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        $this->update();

        $this->createAssociation();

        if ($this->productShouldSyncImage()) {
            $this->uploadImage();
        }
    }

    public function saveLog()
    {
        // Translators: %1$s is the product reference.
        $message = sprintf(__('Simple product updated in Moloni (%1$s)', 'moloni-on'), $this->moloniProduct['reference']);

        Context::logger()->info($message, [
            'tag' => 'service:mlproduct:simple:update',
            'moloniId' => $this->moloniProduct['productId'],
            'moloniParentId' => 0,
            'wcId' => $this->wcProduct->get_id(),
            'wcParentId' => 0,
            'props' => $this->props
        ]);
    }

    //            Privates            //

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
