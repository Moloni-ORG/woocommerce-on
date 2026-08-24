<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Services\MoloniProduct\Helpers\Variants;

use MoloniOn\API\PropertyGroups;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Services\MoloniProduct\Helpers\Abstracts\VariantHelperAbstract;
use WC_Product;
use WC_Product_Variable;

class GetOrUpdatePropertyGroup extends VariantHelperAbstract
{
    /**
     * @var array
     */
    private $productAttributes;

    /**
     * @var string
     */
    private $propertyGroupId;

    /**
     * Constructor
     *
     * @param WC_Product|WC_Product_Variable $wcProduct
     * @param string $propertyGroupId
     *
     * @throws HelperException
     */
    public function __construct($wcProduct, string $propertyGroupId)
    {
        $this->productAttributes = (new ParseProductProperties($wcProduct))->handle();
        $this->propertyGroupId = $propertyGroupId;
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

        $queryParams = [
            'propertyGroupId' => $this->propertyGroupId
        ];

        try {
            $query = PropertyGroups::queryPropertyGroup($queryParams);

            $moloniPropertyGroup = $query['data']['propertyGroup']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new HelperException(__('Error fetching property group', 'moloni-on'));
        }

        /** Propery group is not found, exit process immediately */
        if (empty($moloniPropertyGroup)) {
            throw new HelperException(__('Error fetching property group', 'moloni-on'), ['query' => $query]);
        }

        /**
         * Create any missing property / value through the granular mutations
         * (only the diff is sent, never the whole group)
         */
        $updatedPropertyGroup = $this->ensurePropertiesExist($moloniPropertyGroup, $this->productAttributes);

        return (new PrepareVariantPropertiesReturn($updatedPropertyGroup, $this->productAttributes))->handle();
    }
}
