<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Services\MoloniProduct\Helpers\Variants;

use MoloniOn\API\PropertyGroups;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;
use WC_Product;
use WC_Product_Variable;

class FindOrCreatePropertyGroup extends VariantHelperAbstract
{
    /**
     * @var array
     */
    private $productAttributes;

    /**
     * Constructor
     *
     * @param WC_Product|WC_Product_Variable $wcProduct
     *
     * @throws HelperException
     */
    public function __construct($wcProduct)
    {
        $this->productAttributes = (new ParseProductProperties($wcProduct))->handle();
    }

    /**
     * Handler
     *
     * @throws HelperException
     */
    public function handle(): array
    {
        if (empty($this->productAttributes)) {
            return [];
        }

        try {
            $moloniPropertyGroups = PropertyGroups::queryPropertyGroups();
        } catch (APIExeption $e) {
            throw new HelperException(__('Error fetching property groups', 'moloni-on'));
        }

        /**
         * Always prefer a single "WooCommerce" group when one exists, regardless
         * of how many properties overlap. Reuse it and add any missing
         * property / value through the granular mutations.
         */
        $wooKey = $this->findInName($moloniPropertyGroups, self::VARIANTS_GROUP_NAME);

        if ($wooKey !== false) {
            $wooGroup = $moloniPropertyGroups[$wooKey];
            $wooGroup['properties'] = $wooGroup['properties'] ?? [];

            $updatedGroup = $this->ensurePropertiesExist($wooGroup, $this->productAttributes);

            return (new PrepareVariantPropertiesReturn($updatedGroup, $this->productAttributes))->handle();
        }

        $matches = [];

        /** Try to find the best property group */
        foreach ($moloniPropertyGroups as $moloniPropertyGroup) {
            if (empty($moloniPropertyGroup['propertyGroupId']) || empty($moloniPropertyGroup['properties'])) {
                continue;
            }

            $propertyGroupPropertiesMatchCount = 0;

            foreach ($this->productAttributes as $attributes) {
                foreach ($attributes as $attributeName => $options) {
                    foreach ($moloniPropertyGroup['properties'] as $property) {
                        if (strtolower($attributeName) === strtolower($property['name'])) {
                            $propertyGroupPropertiesMatchCount++;
                        }
                    }
                }
            }

            $matches[] = [
                'propertyGroupId' => $moloniPropertyGroup['propertyGroupId'],
                'count' => $propertyGroupPropertiesMatchCount,
            ];
        }

        unset($attributeName, $options, $moloniPropertyGroup);

        // Sort by best match descending
        $this->orderMatches($matches);

        /**
         * No matches, or the best match is 0
         * We need to fully create it
         */
        if (empty($matches) || $matches[0]['count'] === 0) {
            return (new CreateEntirePropertyGroup($moloniPropertyGroups, $this->productAttributes))->handle();
        }

        /**
         * A match was found
         * If it was partial, we need to do a propertyGroup update to add the missing stuff
         * If it was 100% match, we can just return
         */
        $bestPropertyGroupId = (int)$matches[0]['propertyGroupId'];
        $bestPropertyGroup = $this->findInPropertyGroup($moloniPropertyGroups, $bestPropertyGroupId);

        /**
         * Create any missing property / value through the granular mutations
         * (only the diff is sent, never the whole group)
         */
        $updatedGroup = $this->ensurePropertiesExist($bestPropertyGroup, $this->productAttributes);

        return (new PrepareVariantPropertiesReturn($updatedGroup, $this->productAttributes))->handle();
    }

    //          Privates          //

    /**
     * Orders matches in descending order
     *
     * @param array $matches
     *
     * @return void
     */
    private function orderMatches(array &$matches): void
    {
        $countColumn = array_column($matches, 'count');

        array_multisort($countColumn, SORT_DESC, $matches);
    }
}
