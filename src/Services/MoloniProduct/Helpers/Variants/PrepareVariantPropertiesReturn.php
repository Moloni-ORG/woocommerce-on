<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Services\MoloniProduct\Helpers\Variants;

use MoloniOn\Exceptions\HelperException;
use MoloniOn\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;

class PrepareVariantPropertiesReturn extends VariantHelperAbstract
{

    private $moloniPropertyGroup;
    private $productAttributes;

    public function __construct(array $moloniPropertyGroup, array $productAttributes)
    {
        $this->moloniPropertyGroup = $moloniPropertyGroup;
        $this->productAttributes = $productAttributes;
    }

    /**
     * @throws HelperException
     */
    public function handle(): array
    {
        $result = [];

        foreach ($this->productAttributes as $wcProductId => $attributes) {
            $variantProperties = [];

            foreach ($attributes as $attributesName => $options) {
                foreach ($options as $option) {
                    $propExistsKey = $this->findInName($this->moloniPropertyGroup['properties'], $attributesName);

                    if ($propExistsKey === false) {
                        throw new HelperException(
                            // Translators: %1$s is the property group name.
                            sprintf(__('Failed to find matching property value for "%1$s"', 'moloni-on'), $attributesName)
                        );
                    }

                    $propExists = $this->moloniPropertyGroup['properties'][$propExistsKey];

                    $valueExists = $this->findInCode($propExists['values'], $option);

                    if ($valueExists === false) {
                        throw new HelperException(
                            // Translators: %1$s is the property group name.
                            sprintf(__('Failed to find matching property value for "%1$s"', 'moloni-on'), $option)
                        );
                    }

                    $variantProperties[] = [
                        'propertyId' => $propExists['propertyId'],
                        'propertyValueId' => $valueExists['propertyValueId'],
                    ];
                }
            }

            $result[$wcProductId] = $variantProperties;
        }

        return [
            'propertyGroupId' => $this->moloniPropertyGroup['propertyGroupId'],
            'variations' => $result,
        ];
    }
}
