<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Services\MoloniProduct\Abstracts;

use MoloniOn\Context;
use MoloniOn\Enums\ProductTypeAT;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Helpers\MoloniWarehouse;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Services\MoloniProduct\Helpers\GetOrCreateCategory;
use MoloniOn\Services\MoloniProduct\Helpers\UpdateProductImages;
use WC_Tax;
use WC_Product;
use WC_Product_Variation;
use MoloniOn\Tools;
use MoloniOn\API\Products;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\ProductType;
use MoloniOn\Exceptions\ServiceException;
use MoloniOn\Enums\ProductIdentificationType;
use MoloniOn\Services\MoloniProduct\Variant\MoloniVariant;
use MoloniOn\Services\MoloniProduct\Helpers\Variants\FindOrCreatePropertyGroup;
use MoloniOn\Services\MoloniProduct\Helpers\Variants\GetOrUpdatePropertyGroup;
use MoloniOn\Services\MoloniProduct\Interfaces\MoloniProductServiceInterface;
use MoloniOn\Traits\SyncFieldsSettingsTrait;

abstract class MoloniProductSyncAbstract implements MoloniProductServiceInterface
{
    use SyncFieldsSettingsTrait;

    /**
     * WooCommerce product
     *
     * @var WC_Product|null
     */
    protected $wcProduct;

    /**
     * Moloni Product
     *
     * @var array
     */
    protected $moloniProduct = [];

    /**
     * Create props
     *
     * @var array
     */
    protected $props = [];


    /**
     * WooCommerce's variation products
     *
     * @var WC_Product_Variation[]
     */
    protected $variationProductsCache = [];

    /**
     * Property group
     *
     * @var array
     */
    protected $propertyGroup = [];

    /**
     * Moloni variant services
     *
     * @var MoloniVariant[]
     */
    protected $variantServices = [];

    //            Sets            //

    protected function loadVariationProducts()
    {
        /** Let's load everything at the beginning, to do fewer queries to database */

        $variationIds = $this->wcProduct->get_children();

        if (empty($variationIds)) {
            return;
        }

        foreach ($variationIds as $variationId) {
            $this->variationProductsCache[$variationId] = wc_get_product($variationId);
        }
    }

    protected function setProductId()
    {
        $this->props['productId'] = $this->moloniProduct['productId'] ?? 0;
    }

    protected function setName()
    {
        $this->props['name'] = $this->wcProduct->get_name();
    }

    protected function setReference()
    {
        $reference = $this->wcProduct->get_sku();

        if (empty($reference)) {
            $reference = $this->createReferenceFromString($this->wcProduct->get_name());
        }

        $this->props['reference'] = $reference;
    }

    /**
     * Set category
     *
     * @throws ServiceException
     */
    protected function setCategory()
    {
        $categoryId = 0;
        $categories = $this->wcProduct->get_category_ids();

        /** Get the deepest category from all the trees */
        if (!empty($categories) && is_array($categories)) {
            $categoryTree = [];

            foreach ($categories as $category) {
                $parents = get_ancestors($category, 'product_cat');
                $parents = array_reverse($parents);
                $parents[] = $category;

                if (is_array($parents) && count($parents) > count($categoryTree)) {
                    $categoryTree = $parents;
                }
            }

            foreach ($categoryTree as $category) {
                $category = get_term_by('id', $category, 'product_cat');

                if (!empty($category->name)) {
                    try {
                        $categoryId = (new GetOrCreateCategory($category->name, $categoryId))->get();
                    } catch (HelperException $e) {
                        throw new ServiceException($e->getMessage(), $e->getData());
                    }
                }
            }
        }

        if ($categoryId === 0) {
            try {
                $categoryId = (new GetOrCreateCategory(__('Online Store', 'moloni-on')))->get();
            } catch (HelperException $e) {
                throw new ServiceException($e->getMessage(), $e->getData());
            }
        }

        $this->props['productCategoryId'] = $categoryId;
    }

    protected function setEan()
    {
        $identifications = [];
        $isEanFav = false;

        if (isset($this->moloniProduct['identifications']) && !empty($this->moloniProduct['identifications'])) {
            foreach ($this->moloniProduct['identifications'] as $identification) {
                if ($identification['type'] === ProductIdentificationType::EAN13) {
                    $isEanFav = $identification['favorite'];
                } else {
                    $identifications[] = $identification;
                }
            }
        }

        $metaBarcode = $this->wcProduct->get_meta('barcode', true);

        if (!empty($metaBarcode)) {
            $identifications[] = [
                'type' => 'EAN13',
                'text' => $metaBarcode,
                'favorite' => $isEanFav
            ];
        }

        $this->props['identifications'] = $identifications;
    }

    protected function setType()
    {
        if ($this->wcProduct->is_virtual() || $this->wcProduct->is_downloadable()) {
            $this->props['type'] = ProductType::SERVICE;
        } else {
            $this->props['type'] = ProductType::PRODUCT;
        }
    }

    protected function setTypeAt()
    {
        $this->props['productAT'] = [
            'productType' => ProductTypeAT::GOODS
        ];
    }

    /**
     * Set stock
     *
     * @throws ServiceException
     */
    protected function setStock()
    {
        $wcProductIsVariable = $this->wcProduct->is_type('variable');

        if ($wcProductIsVariable) {
            $hasStock = false;

            foreach ($this->variationProductsCache as $variationProduct) {
                if ($variationProduct->managing_stock()) {
                    $hasStock = true;

                    break;
                }
            }
        } else {
            $hasStock = $this->wcProduct->managing_stock();
        }

        $this->props['hasStock'] = $hasStock;

        if ($hasStock) {
            $warehouseId = Context::settings()->getInt('moloni_stock_sync_warehouse');

            if (empty($warehouseId)) {
                try {
                    $warehouseId = MoloniWarehouse::getDefaultWarehouseId();
                } catch (HelperException $e) {
                    throw new ServiceException($e->getMessage(), $e->getData());
                }
            }

            $this->props['warehouseId'] = $warehouseId;

            if ($wcProductIsVariable) {
                $this->props['warehouses'] = [
                    'warehouseId' => $warehouseId,
                ];
            } else {
                $this->props['warehouses'] = [[
                    'warehouseId' => $warehouseId,
                    'stock' => (float)$this->wcProduct->get_stock_quantity()
                ]];
            }
        }
    }

    protected function setPrice()
    {
        $this->props['price'] = round((float)wc_get_price_excluding_tax($this->wcProduct), 5);
    }

    protected function setSummary()
    {
        $this->props['summary'] = $this->wcProduct->get_short_description() ?? '';
    }

    protected function setNotes()
    {
        $this->props['notes'] = $this->wcProduct->get_description() ?? '';
    }

    protected function setMeasureUnit()
    {
        $this->props['measurementUnitId'] = Context::settings()->getInt('measure_unit');
    }

    protected function setTaxes()
    {
        $this->props['taxes'] = [];
        $this->props['exemptionReason'] = '';

        if ($this->wcProduct->get_tax_status() === 'taxable') {
            // Get taxes based on a tax class of a product
            // If the tax class is empty it means the products uses the shop default
            $productTaxes = $this->wcProduct->get_tax_class();
            $taxRates = WC_Tax::get_base_tax_rates($productTaxes);

            $fiscalZone = [
                'code' => Context::company()->get('fiscalZone')['fiscalZone'],
                'countryId' => Context::company()->get('country')['countryId'],
            ];

            foreach ($taxRates as $order => $taxRate) {
                if ((float)$taxRate['rate'] <= 0) {
                    continue;
                }

                $moloniTax = Tools::getTaxFromRate((float)$taxRate['rate'], $fiscalZone);

                $tax = [];
                $tax['taxId'] = (int)$moloniTax['taxId'];
                $tax['value'] = (float)$moloniTax['value'];
                $tax['ordering'] = (int)$order;
                $tax['cumulative'] = false;

                if ($moloniTax['value'] > 0) {
                    $this->props['taxes'][] = $tax;
                }
            }
        }

        if (empty($this->props['taxes'])) {
            $this->props['exemptionReason'] = Context::settings()->getString('exemption_reason');
        }
    }

    /**
     * Set property groups
     *
     * @throws ServiceException
     */
    protected function setPropertyGroup()
    {
        if (empty($this->moloniProduct)) {
            $targetId = '';
        } else {
            $targetId = $this->moloniProduct['propertyGroup']['propertyGroupId'] ?? '';
        }

        try {
            if (empty($targetId)) {
                $propertyGroup = (new FindOrCreatePropertyGroup($this->wcProduct))->handle();
            } else {
                $propertyGroup = (new GetOrUpdatePropertyGroup($this->wcProduct, $targetId))->handle();
            }
        } catch (HelperException $e) {
            throw new ServiceException($e->getMessage(), $e->getData());
        }

        $this->propertyGroup = $propertyGroup;

        $this->props['propertyGroupId'] = $propertyGroup['propertyGroupId'];
    }

    /**
     * Build the variant services (props are prepared here, but each variant is
     * only persisted later in saveVariants(), once the parent product exists).
     *
     * @throws ServiceException
     */
    protected function setVariants()
    {
        foreach ($this->propertyGroup['variations'] as $wcVariationId => $targetPropertyGroup) {
            /** Get variation from cached objects array */
            $wcVariation = $this->variationProductsCache[$wcVariationId] ?? null;

            if (empty($wcVariation)) {
                continue;
            }

            $service = new MoloniVariant(
                $wcVariation,
                $this->moloniProduct ?? [],
                $this->propertyGroup['variations'][$wcVariationId] ?? []
            );
            $service->findVariant();
            $service->run();

            $this->variantServices[] = $service;
        }
    }

    //            Requests            //

    /**
     * @throws ServiceException
     */
    protected function insert()
    {
        $data = [
            'data' => $this->props
        ];

        $data = apply_filters('moloni_on_before_moloni_product_insert', $data);

        try {
            $mutation = Products::mutationProductCreate($data);
        } catch (APIExeption $e) {
            throw new ServiceException(
                sprintf(
                    // Translators: %1$s is the action. %2$s is the product SKU.
                    __('Error %1$s product in Moloni ON (%2$s)', 'moloni-on'),
                    __('creating', 'moloni-on'),
                    $this->props['reference'] ?? '---'
                ),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $product = $mutation['data']['productCreate']['data'] ?? [];

        if (empty($product)) {
            throw new ServiceException(
                sprintf(
                    // Translators: %1$s is the action. %2$s is the product SKU.
                    __('Error %1$s product in Moloni ON (%2$s)', 'moloni-on'),
                    __('creating', 'moloni-on'),
                    $this->props['reference'] ?? '---'
                ),
                [
                    'mutation' => $mutation,
                    'data' => $data,
                ]
            );
        }

        $this->moloniProduct = $product;

        /** Persist the parent mapping before touching variants, so a failure
         *  mid-variant leaves a recoverable association instead of an orphan */
        $this->associateParent();

        $this->saveVariants();
    }

    /**
     * Update a Moloni product
     *
     * @throws ServiceException
     */
    protected function update()
    {
        $data = [
            'data' => $this->props
        ];

        $data = apply_filters('moloni_on_before_moloni_product_update', $data);

        try {
            $mutation = Products::mutationProductUpdate($data);
        } catch (APIExeption $e) {
            throw new ServiceException(
                sprintf(
                    // Translators: %1$s is the action. %2$s is the product SKU.
                    __('Error %1$s product in Moloni ON (%2$s)', 'moloni-on'),
                    __('updating', 'moloni-on'),
                    $this->wcProduct->get_sku() ?? '---'
                ),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $product = $mutation['data']['productUpdate']['data'] ?? [];

        if (empty($product)) {
            throw new ServiceException(
                sprintf(
                    // Translators: %1$s is the action. %2$s is the product SKU.
                    __('Error %1$s product in Moloni ON (%2$s)', 'moloni-on'),
                    __('updating', 'moloni-on'),
                    $this->wcProduct->get_sku() ?? '---'
                ),
                [
                    'mutation' => $mutation,
                    'data' => $data,
                ]
            );
        }

        $this->moloniProduct = $product;

        /** Persist the parent mapping before touching variants, so a failure
         *  mid-variant leaves a recoverable association instead of an orphan */
        $this->associateParent();

        $this->saveVariants();
    }

    protected function uploadImage()
    {
        $files = [];
        $wcImageId = $this->wcProduct->get_image_id();

        $url = wp_get_attachment_url($wcImageId);

        if ($url) {
            $uploads = wp_upload_dir();

            $files[0] = [
                'id' => $wcImageId,
                'file' => str_replace($uploads['baseurl'], $uploads['basedir'], $url)
            ];
        } else {
            $files[0] = [
                'id' => '',
                'file' => '',
            ];
        }

        if (!empty($this->variantServices)) {
            foreach ($this->variantServices as $variantService) {
                $file = $variantService->getImage();
                $productId = $variantService->getMoloniVariantProductId();

                $files[$productId] = [
                    'id' => $variantService->getWcProduct()->get_image_id(),
                    'file' => $file,
                ];
            }
        }

        new UpdateProductImages($files, $this->moloniProduct);
    }

    //            Gets            //

    public function getWcProduct(): ?WC_Product
    {
        return $this->wcProduct;
    }

    public function getMoloniProduct(): array
    {
        return $this->moloniProduct;
    }

    //            Auxiliary            //

    /**
     * Persist the parent product ↔ WooCommerce association.
     *
     * Called right after the parent is saved and before any variant work, so an
     * error while saving variants still leaves a recoverable mapping (avoiding
     * an orphaned Moloni parent that a retry would duplicate or reject on the
     * unique-reference constraint).
     */
    protected function associateParent()
    {
        ProductAssociations::deleteByWcId($this->wcProduct->get_id());
        ProductAssociations::deleteByMoloniId((int)$this->moloniProduct['productId']);

        ProductAssociations::add(
            $this->wcProduct->get_id(),
            0,
            $this->moloniProduct['productId'],
            0
        );
    }

    /**
     * Persist every variant through the granular mutations, then reconcile
     * variants that were removed in WooCommerce.
     *
     * Runs after the parent product has been saved, so productVariantCreate
     * has a parent to attach new variants to.
     *
     * @throws ServiceException
     */
    protected function saveVariants()
    {
        /** Variants that are still present (created or updated) in this sync */
        $keptVariants = [];

        foreach ($this->variantServices as $variantService) {
            $variantService->setMoloniParentProduct($this->moloniProduct);
            $variantService->save();

            $variant = $variantService->getMoloniVariant();

            if (!empty($variant)) {
                $keptVariants[] = $variant;
            }
        }

        $hiddenVariants = $this->reconcileRemovedVariants($keptVariants);

        /**
         * Keep the parent product's variant list coherent for the steps that
         * still run after this (e.g. image upload): the create/update response
         * doesn't include the variants we just persisted granularly, and the
         * image upload still ships the full variant list.
         */
        $this->moloniProduct['variants'] = array_merge($keptVariants, $hiddenVariants);
    }

    /**
     * Deletes (or hides, when not deletable) Moloni variants that no longer
     * exist in WooCommerce. With the granular flow omitted variants are no
     * longer removed automatically, so we handle it explicitly here.
     *
     * Returns the variants that were hidden (kept in Moloni), so the caller can
     * keep them in the product's variant list.
     *
     * @param array $keptVariants Variants created/updated in this sync
     *
     * @return array
     *
     * @throws ServiceException
     */
    protected function reconcileRemovedVariants(array $keptVariants): array
    {
        if (empty($this->moloniProduct['variants'])) {
            return [];
        }

        $keptProductIds = array_map(static function ($variant) {
            return (int)($variant['productId'] ?? 0);
        }, $keptVariants);

        $hiddenVariants = [];
        $toDelete = [];

        foreach ($this->moloniProduct['variants'] as $existingVariant) {
            if (in_array((int)$existingVariant['productId'], $keptProductIds, true)) {
                continue;
            }

            /** This variant no longer exists in WooCommerce */
            if ($existingVariant['deletable'] === false) {
                /** Can't be deleted (has documents/movements), hide it instead */
                try {
                    Products::mutationProductUpdate([
                        'data' => [
                            'productId' => (int)$existingVariant['productId'],
                            'visible' => Boolean::NO,
                        ],
                    ]);
                } catch (APIExeption $e) {
                    throw new ServiceException(
                        __('Error hiding removed variant in Moloni ON', 'moloni-on'),
                        ['message' => $e->getMessage(), 'data' => $e->getData()]
                    );
                }

                $existingVariant['visible'] = Boolean::NO;
                $hiddenVariants[] = $existingVariant;
            } else {
                $toDelete[] = (int)$existingVariant['productId'];
            }
        }

        if (!empty($toDelete)) {
            try {
                Products::mutationProductDelete(['productId' => $toDelete]);
            } catch (APIExeption $e) {
                throw new ServiceException(
                    __('Error deleting removed variants in Moloni ON', 'moloni-on'),
                    ['message' => $e->getMessage(), 'data' => $e->getData()]
                );
            }
        }

        return $hiddenVariants;
    }

    /**
     * Creates reference for product if missing
     *
     * @param string $string
     *
     * @return string
     */
    protected function createReferenceFromString(string $string): string
    {
        $reference = '';
        $name = explode(' ', $string);

        foreach ($name as $word) {
            $reference .= '_' . mb_substr($word, 0, 3);
        }

        return $reference;
    }

    //            Abstracts            //

    protected abstract function createAssociation();
}
