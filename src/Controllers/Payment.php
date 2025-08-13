<?php

namespace MoloniOn\Controllers;

use MoloniOn\API\PaymentMethods;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\DocumentError;

class Payment
{
    public $payment_method_id;
    public $name;
    public $value = 0;

    /**
     * Payment constructor.
     *
     * @param string|null $name
     */
    public function __construct(?string $name = '')
    {
        $this->name = trim($name);
    }

    /**
     * Loads a payment method by name
     *
     * @throws DocumentError
     */
    public function loadByName()
    {
        $variables = [
            'options' => [
                'search' => [
                    'field' => 'name',
                    'value' => $this->name
                ]
            ]
        ];

        try {
            $paymentMethods = PaymentMethods::queryPaymentMethods($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching payment methods', 'moloni-on'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod['name'] === $this->name) {
                    $this->payment_method_id = (int)$paymentMethod['paymentMethodId'];
                    return $this;
                }
            }
        }

        return false;
    }

    /**
     * Create a Payment Methods based on the name
     *
     * @throws DocumentError
     */
    public function create(): Payment
    {
        try {
            $mutation = (PaymentMethods::mutationPaymentMethodCreate($this->mapPropsToValues()))['data']['paymentMethodCreate']['data'] ?? [];
        } catch (APIExeption $e) {
            // Translators: %1$s is the payment name.
            $log = sprintf(__('Error creating payment method (%1$s)', 'moloni-on'), $this->name);

            throw new DocumentError($log, [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if (isset($mutation['paymentMethodId'])) {
            $this->payment_method_id = $mutation['paymentMethodId'];
            return $this;
        }

        // Translators: %1$s is the payment name.
        $log = sprintf(__('Error creating payment method (%1$s)', 'moloni-on'), $this->name);

        throw new DocumentError($log, [
                'mutation' => $mutation
            ]
        );
    }

    /**
     * Map this object properties to an array to insert/update a moloni Payment Value
     *
     * @return array
     */
    private function mapPropsToValues(): array
    {
        return [
            'data' => [
                'name' => $this->name
            ]
        ];
    }
}
