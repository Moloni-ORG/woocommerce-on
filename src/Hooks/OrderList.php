<?php

namespace MoloniOn\Hooks;

use MoloniOn\Helpers\Security;
use WC_Order;
use Exception;
use MoloniOn\Start;
use MoloniOn\Plugin;
use MoloniOn\Context;
use MoloniOn\Helpers\MoloniOrder;

/**
 * Class OrderList
 * Add a Moloni column orders list
 */
class OrderList
{
    /**
     * Caller class
     *
     * @var Plugin
     */
    public $parent;

    /**
     * If user want to show Moloni column
     * @var null|bool
     */
    private static $columnVisible;

    /**
     * OrderList constructor
     *
     * @param Plugin $parent Caller
     *
     * @return void
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        if (Context::$USES_NEW_ORDERS_SYSTEM) {
            /**
             * HPOS usage is enabled.
             *
             * @see https://github.com/woocommerce/woocommerce/issues/35049
             * @see https://developer.woocommerce.com/2022/10/11/hpos-upgrade-faqs/
             */
            add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'ordersListAddColumn'], 10, 1);
            add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'ordersListManageColumn'], 10, 2);
        } else {
            /**
             * Traditional CPT-based orders are in use.
             */
            add_filter('manage_edit-shop_order_columns', [$this, 'ordersListAddColumn'], 10, 1);
            add_action('manage_shop_order_posts_custom_column', [$this, 'ordersListManageColumn'], 10, 2);
        }
    }

    /**
     * Appends Moloni column list
     *
     * @param array $oldColumns Columns list
     *
     * @return array
     */
    public function ordersListAddColumn(array $oldColumns): array
    {
        if (!$this->canShowColumn()) {
             return $oldColumns;
        }

        $newColumns = [];

        foreach ($oldColumns as $name => $info) {
            $newColumns[$name] = $info;

            if ('order_status' === $name) {
                $newColumns['moloni_document'] = __('Moloni ON document', 'moloni-on');
            }
        }

        return $newColumns;
    }

    /**
     * Draws Moloni column content
     *
     * @param string $currentColumnName Current column name
     * @param $orderOrPostId
     *
     * @return void
     */
    public function ordersListManageColumn(string $currentColumnName, $orderOrPostId)
    {
        if (!$this->canShowColumn()) {
            return;
        }

        if ($currentColumnName === 'moloni_document') {
            $order = new WC_Order($orderOrPostId);

            $documentId = MoloniOrder::getLastCreatedDocument($order);

            if ($documentId > 0) {
                $html = '<a class="button" target="_blank" href="' . esc_url($this->getDownloadUrl($documentId, $order->get_id())) . '">' . __('Download', 'moloni-on') . '</a>';
            } else {
                $html = '<div>' . __('No associated document', 'moloni-on') . '</div>';
            }

            echo wp_kses_post($html);
        }
    }

    /**
     * Verifies if user wants to show column
     *
     * @return bool
     */
    private function canShowColumn(): ?bool
    {
        if (self::$columnVisible === null) {
            try {
                self::$columnVisible = (new Start())->isFullyAuthed() && Context::settings()->getInt('moloni_show_download_column') === 1;
            } catch (Exception $e) {
                self::$columnVisible = false;
            }
        }

        return self::$columnVisible;
    }

    /**
     * Gives the download button action
     *
     * @param int $orderId
     * @param int $documentId
     *
     * @return string
     */
    private function getDownloadUrl(int $documentId, int $orderId): string
    {
        $url = add_query_arg([
            'action' => 'molonion_download_order_document',
            'order_id' => $orderId,
            'document_id' => $documentId,
        ], admin_url('admin-post.php'));

        return Security::get_nonce_url($url);
    }
}
