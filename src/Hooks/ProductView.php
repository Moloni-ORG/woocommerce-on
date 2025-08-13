<?php

namespace MoloniOn\Hooks;

use Exception;
use MoloniOn\API\Products;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Models\ProductAssociations;
use MoloniOn\Plugin;
use MoloniOn\Start;
use WC_Product;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the product view
 * @package Moloni\Hooks
 */
class ProductView
{
    /** @var Plugin  */
    public $parent;

    /** @var WC_Product */
    public $wcProduct;

    /** @var array */
    public $moloniProduct = [];

    private $allowedPostTypes = ["product"];

    /**
     * Contructor
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box($post_type)
    {
        if (in_array($post_type, $this->allowedPostTypes)) {
            add_meta_box('woocommerce_product_options_general_product_data', 'Moloni', [$this, 'showMoloniView'], null, 'side');
        }
    }

    /**
     * @return null|void
     */
    public function showMoloniView()
    {
        try {
            if (Start::login(true)) {
                $this->wcProduct = wc_get_product(get_the_ID());

                if (!$this->wcProduct) {
                    return null;
                }

                try {
                    $this->fetchMoloniProduct();

                    if (empty($this->moloniProduct)) {
                        echo __("Product not found in Moloni", 'moloni-on');
                        return null;
                    }

                    $this->showProductDetails();
                } catch (APIExeption $e) {
                    echo __("Error getting product", 'moloni-on');
                    return null;
                }
            } else {
                echo __("Moloni login invalid", 'moloni-on');
            }
        } catch (Exception $exception) {}
    }

    private function showProductDetails()
    {
        ?>
        <div>
            <p>
                <b><?php echo __("Reference: ", 'moloni-on') ?></b> <?php echo $this->moloniProduct['reference'] ?><br>
                <b><?php echo __("Price: ", 'moloni-on') ?></b> <?php echo $this->moloniProduct['price'] ?>€<br>

                <?php if ((int)$this->moloniProduct['hasStock'] === Boolean::YES) : ?>
                    <b><?php echo __("Stock: ", 'moloni-on') ?></b> <?php echo $this->moloniProduct['stock'] ?>
                <?php endif; ?>

                <?php

                echo "<pre style='display: none'>";
                print_r($this->wcProduct->get_meta_data());
                print_r($this->wcProduct->get_default_attributes());
                print_r($this->wcProduct->get_attributes());
                print_r($this->wcProduct->get_data());
                echo "</pre>";

                ?>
            </p>
            <?php if (defined("COMPANY_SLUG")) : ?>
                <a type="button"
                   class="button button-primary"
                   target="_BLANK"
                   href="<?php echo Context::configs()->get('ac_url') . COMPANY_SLUG . '/productCategories/products/' . $this->moloniProduct['productId'] ?>"
                > <?php echo __("See product", 'moloni-on') ?> </a>
            <?php endif; ?>
        </div>
        <?php
    }

    //          REQUESTS          //

    /**
     * Fetch Moloni Product
     *
     * @throws APIExeption
     */
    private function fetchMoloniProduct()
    {
        /** Fetch by our associations table */

        $association = ProductAssociations::findByWcId($this->wcProduct->get_id());

        if (!empty($association)) {
            $byId = Products::queryProduct(['productId' => (int)$association['ml_product_id']]);
            $byId = $byId['data']['product']['data'] ?? [];

            if (!empty($byId)) {
                $this->moloniProduct = $byId;

                return;
            }

            ProductAssociations::deleteById($association['id']);
        }

        if (empty($this->wcProduct->get_sku())) {
            return;
        }

        $variables = [
            'options' => [
                'filter' => [
                    [
                        'field' => 'reference',
                        'comparison' => 'eq',
                        'value' => $this->wcProduct->get_sku(),
                    ],
                    [
                        'field' => 'visible',
                        'comparison' => 'in',
                        'value' => '[0, 1]'
                    ]
                ]
            ]
        ];

        $byReference = Products::queryProducts($variables)['data']['products']['data'] ?? [];

        if (!empty($byReference) && isset($byReference[0]['productId'])) {
            $this->moloniProduct = $byReference[0];
        }
    }
}
