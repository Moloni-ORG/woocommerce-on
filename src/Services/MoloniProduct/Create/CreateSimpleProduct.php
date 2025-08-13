<?php

namespace MoloniOn\Services\MoloniProduct\Create;

use MoloniOn\Context;
use MoloniOn\Exceptions\ServiceException;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\MoloniProduct\Abstracts\MoloniProductSyncAbstract;
use WC_Product;

class CreateSimpleProduct extends MoloniProductSyncAbstract
{
    public function __construct(WC_Product $wcProduct)
    {
        $this->wcProduct = $wcProduct;
    }

    //            Publics            //

    /**
     * Runner
     *
     * @throws ServiceException
     */
    public function run()
    {
        $this->setName();
        $this->setReference();
        $this->setPrice();
        $this->setTaxes();
        $this->setCategory();
        $this->setType();
        $this->setTypeAT();
        $this->setMeasureUnit();

        if ($this->productShouldSyncDescription()) {
            $this->setSummary();
            $this->setNotes();
        }

        if ($this->productShouldSyncEAN()) {
            $this->setEan();
        }

        if ($this->productShouldSyncStock()) {
            $this->setStock();
        }

        $this->insert();

        $this->createAssociation();

        if ($this->productShouldSyncImage()) {
            $this->uploadImage();
        }
    }

    public function saveLog()
    {
        // Translators: %1$s is the product reference.
        $message = sprintf(__('Simple product created in Moloni (%1$s)', 'moloni-on'), $this->moloniProduct['reference']);

        Context::logger()->info($message, [
            'tag' => 'service:mlproduct:simple:create',
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
        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }
}
