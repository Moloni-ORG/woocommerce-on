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
     * Single shared group model: every product-with-variants uses ONE property
     * group named "WooCommerce", found (by name) or created. Its properties/values
     * grow as new variant options appear, through the granular mutations.
     *
     * @throws HelperException
     */
    public function handle(): array
    {
        if (empty($this->productAttributes)) {
            return [];
        }

        $wooGroup = $this->findGroupByName(self::VARIANTS_GROUP_NAME);

        /** No "WooCommerce" group yet, create it from scratch */
        if (empty($wooGroup)) {
            return (new CreateEntirePropertyGroup($this->productAttributes))->handle();
        }

        /**
         * Reuse the "WooCommerce" group, adding any missing property / value
         * through the granular mutations (only the diff is sent, never the whole group).
         */
        $updatedGroup = $this->ensurePropertiesExist($wooGroup, $this->productAttributes);

        return (new PrepareVariantPropertiesReturn($updatedGroup, $this->productAttributes))->handle();
    }

    //          Privates          //

    /**
     * Fetches the property group with the given name.
     *
     * Uses the server-side name search to avoid pulling every group, then confirms
     * the exact name in code (the API search is a partial LIKE match).
     *
     * @return array Empty array when no group with that exact name exists
     *
     * @throws HelperException
     */
    private function findGroupByName(string $name): array
    {
        try {
            $moloniPropertyGroups = PropertyGroups::queryPropertyGroups([
                'options' => [
                    'search' => [
                        'field' => 'name',
                        'value' => $name,
                    ],
                ],
            ]);
        } catch (APIExeption $e) {
            throw new HelperException(__('Error fetching property groups', 'moloni-on'));
        }

        $key = $this->findInName($moloniPropertyGroups, $name);

        if ($key === false) {
            return [];
        }

        $group = $moloniPropertyGroups[$key];
        $group['properties'] = $group['properties'] ?? [];

        return $group;
    }
}
