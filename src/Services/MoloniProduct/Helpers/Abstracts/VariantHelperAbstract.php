<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Services\MoloniProduct\Helpers\Abstracts;

use MoloniOn\API\PropertyGroups;
use MoloniOn\Enums\Boolean;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;

abstract class VariantHelperAbstract
{
    /** Fixed name used for the WooCommerce variants property group */
    protected const VARIANTS_GROUP_NAME = 'WooCommerce';

    /**
     * Ensures every WooCommerce attribute/option is present in the given Moloni
     * property group, creating any missing property or value through the granular
     * propertyCreate / propertyValueCreate mutations (instead of re-sending the
     * whole group). Returns the property group with the newly created ids merged in.
     *
     * @param array $group Moloni property group (as returned by the API)
     * @param array $productAttributes Parsed WooCommerce attributes
     *
     * @return array
     *
     * @throws HelperException
     */
    protected function ensurePropertiesExist(array $group, array $productAttributes): array
    {
        $propertyGroupId = $group['propertyGroupId'];

        foreach ($productAttributes as $attributes) {
            foreach ($attributes as $attributeName => $options) {
                foreach ($options as $option) {
                    $propKey = $this->findInName($group['properties'], $attributeName);

                    /** Property name doesn't exist, create the property with this value */
                    if ($propKey === false) {
                        $group['properties'][] = $this->createProperty(
                            $propertyGroupId,
                            $attributeName,
                            $option,
                            $this->getNextPropertyOrder($group['properties'])
                        );

                        continue;
                    }

                    /** Property exists, make sure the value exists too */
                    if ($this->findInCode($group['properties'][$propKey]['values'], $option) === false) {
                        $group['properties'][$propKey]['values'][] = $this->createPropertyValue(
                            $group['properties'][$propKey]['propertyId'],
                            $option,
                            $this->getNextPropertyOrder($group['properties'][$propKey]['values'])
                        );
                    }
                }
            }
        }

        return $group;
    }

    /**
     * Creates a single property (with its first value) in an existing group
     *
     * @throws HelperException
     */
    private function createProperty(string $propertyGroupId, string $name, string $option, int $ordering): array
    {
        $variables = [
            'propertyGroupId' => $propertyGroupId,
            'data' => [
                'name' => $name,
                'visible' => Boolean::YES,
                'ordering' => $ordering,
                'values' => [
                    [
                        'code' => $this->cleanReferenceString($option),
                        'value' => $option,
                        'visible' => Boolean::YES,
                        'ordering' => 1,
                    ],
                ],
            ],
        ];

        try {
            $mutation = PropertyGroups::mutationPropertyCreate($variables);
        } catch (APIExeption $e) {
            throw new HelperException(
                // Translators: %1$s is the property name.
                sprintf(__('Failed to create property "%1$s"', 'moloni-on'), $name),
                ['message' => $e->getMessage(), 'data' => $e->getData()]
            );
        }

        $created = $mutation['data']['propertyCreate']['data'] ?? [];

        if (empty($created)) {
            throw new HelperException(
                // Translators: %1$s is the property name.
                sprintf(__('Failed to create property "%1$s"', 'moloni-on'), $name),
                ['mutation' => $mutation, 'variables' => $variables]
            );
        }

        return $created;
    }

    /**
     * Creates a single value in an existing property
     *
     * @throws HelperException
     */
    private function createPropertyValue(string $propertyId, string $option, int $ordering): array
    {
        $variables = [
            'propertyId' => $propertyId,
            'data' => [
                'code' => $this->cleanReferenceString($option),
                'value' => $option,
                'visible' => Boolean::YES,
                'ordering' => $ordering,
            ],
        ];

        try {
            $mutation = PropertyGroups::mutationPropertyValueCreate($variables);
        } catch (APIExeption $e) {
            throw new HelperException(
                // Translators: %1$s is the property value.
                sprintf(__('Failed to create property value "%1$s"', 'moloni-on'), $option),
                ['message' => $e->getMessage(), 'data' => $e->getData()]
            );
        }

        $created = $mutation['data']['propertyValueCreate']['data'] ?? [];

        if (empty($created)) {
            throw new HelperException(
                // Translators: %1$s is the property value.
                sprintf(__('Failed to create property value "%1$s"', 'moloni-on'), $option),
                ['mutation' => $mutation, 'variables' => $variables]
            );
        }

        return $created;
    }


    protected function findInName(array $array, string $needle)
    {
        /** Case/whitespace-insensitive so a name the API stored slightly
         *  differently (trim, casing) still matches and we don't create a
         *  duplicate property for it */
        $needle = trim(strtolower($needle));

        foreach ($array as $key => $value) {
            if (trim(strtolower($value['name'])) === $needle) {
                return $key;
            }
        }

        return false;
    }

    protected function findInCode(array $array, string $needle)
    {
        $needle = $this->cleanReferenceString($needle);

        foreach ($array as $value) {
            $value['code'] = $this->cleanReferenceString($value['code']);

            if ($value['code'] === $needle) {
                return $value;
            }
        }

        return false;
    }

    protected function findInPropertyGroup(array $array, int $needle)
    {
        foreach ($array as $value) {
            if ((int)$value['propertyGroupId'] === $needle) {
                return $value;
            }
        }

        return false;
    }

    protected function cleanReferenceString(string $string, int $truncate = 30): string
    {
        return substr($this->cleanCodeString($string), 0, $truncate);
    }

    protected function cleanCodeString(string $string): string
    {
        //Remove end and start spacing
        $string = trim($string);

        // All chars upper case
        $string = strtoupper($string);

        // Remove special chars
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

        // Replaces all double spaces left
        // Replaces all spaces with hyphens
        return str_replace(['  ', ' '], [' ', '-'], $string);
    }

    protected function getNextPropertyOrder(?array $properties = []): int
    {
        $lastOrder = 0;

        if (!empty($properties)) {
            $count = count($properties);
            $lastIndex = $count - 1;

            $lastOrder = $properties[$lastIndex]['ordering'] ?? 0;
        }

        return $lastOrder + 1;
    }
}
