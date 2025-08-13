<?php

namespace MoloniOn\Services\Orders;

use MoloniOn\Context;
use WC_Order;

class DiscardOrder
{
    private $order;

    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    public function run()
    {
        $this->order->add_meta_data('_molonion_sent', '-1');
        $this->order->add_order_note(__('Order was discarded', 'moloni-on'));
        $this->order->save();
    }

    public function saveLog()
    {
        // Translators: %1$s is the order number.
        $message = sprintf(__('Order was discarded (%s)', 'moloni-on'),
            $this->order->get_order_number()
        );

        Context::logger()->info($message, [
            'tag' => 'service:order:discard',
            'order_id' => $this->order->get_id()
        ]);
    }
}
