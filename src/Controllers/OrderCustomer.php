<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn\Controllers;

use MoloniOn\Context;
use WC_Order;
use MoloniOn\Exceptions\DocumentError;
use MoloniOn\API\Customers;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\Countries;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Helpers\Customer;
use MoloniOn\Tools;

class OrderCustomer
{
    /**
     * @var WC_Order
     */
    private $order;

    private $customer_id = false;
    private $vat = null;
    private $email = '';
    private $name = 'Cliente';
    private $contactName = '';
    private $zipCode = '10000';
    private $address = 'Desconocido';
    private $city = 'Desconocido';
    private $languageId;
    private $countryId;

    public function __construct(WC_Order $order)
    {
        $this->order = $order;
    }

    /**
     * Save client
     *
     * @return bool|int
     *
     * @throws DocumentError
     */
    public function create()
    {
        $this->setLanguageAndCountryId();

        $this->email = $this->order->get_billing_email();
        $this->vat = $this->getVatNumber();

        $variables = [
            'name' => $this->getCustomerName(),
            'address' => $this->getCustomerBillingAddress(),
            'zipCode' => $this->getCustomerZip(),
            'city' => $this->getCustomerBillingCity(),
            'countryId' => $this->countryId,
            'languageId' => $this->languageId,
            'vat' => $this->vat,
            'email' => $this->email,
            'phone' => $this->order->get_billing_phone(),
            'contactName' => $this->contactName,
            'maturityDateId' => Context::settings()->getInt('maturity_date') ?: null,
            'paymentMethodId' => Context::settings()->getInt('payment_method') ?: null,
        ];

        $variables = apply_filters('moloni_on_before_search_customer', $variables);

        if (!empty($variables['customerId'])) {
            $this->customer_id = (int)$variables['customerId'];

            return $this->customer_id;
        }

        $customerExists = $this->searchForCustomer();

        if (empty($customerExists)) {
            $variables['number'] = self::getCustomerNextNumber();

            try {
                $result = Customers::mutationCustomerCreate(['data' => $variables]);
            } catch (APIExeption $e) {
                throw new DocumentError(
                    __('Error creating customer.', 'moloni-on'),
                    [
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            }

            $keyString = 'customerCreate';
        } else {
            $variables['customerId'] = (int)$customerExists['customerId'];

            if (empty($customerExists['vat']) && !$customerExists['deletable']) {
                unset($variables['name']);
            }

            try {
                $result = Customers::mutationCustomerUpdate(['data' => $variables]);
            } catch (APIExeption $e) {
                throw new DocumentError(
                    __('Error updating customer.', 'moloni-on'),
                    [
                        'message' => $e->getMessage(),
                        'data' => $e->getData()
                    ]
                );
            }

            $keyString = 'customerUpdate';
        }

        if (isset($result['data'][$keyString]['data']['customerId'])) {
            $this->customer_id = $result['data'][$keyString]['data']['customerId'];
        } else {
            throw new DocumentError(__('There was an error saving the customer.', 'moloni-on'));
        }

        return $this->customer_id;
    }

    //          Gets          //

    /**
     * Get the vat number of an order
     * Get it from a custom field and validate if Portuguese
     *
     * @return string
     *
     * @throws DocumentError
     */
    public function getVatNumber(): ?string
    {
        $vat = null;
        $vatField = Context::settings()->getString('vat_field');

        if (!empty($vatField)) {
            $metaVat = trim($this->order->get_meta($vatField));

            if (!empty($metaVat)) {
                $vat = $metaVat;
            }
        }

        if (empty($vat)) {
            return null;
        }

        $isValid = true;

        if ($this->countryId === Countries::PORTUGAL) {
            $vat = strtoupper($vat);

            if (stripos($vat, 'PT') === 0) {
                $vat = str_ireplace('PT', '', $vat);
            }

            $isValid = Customer::isVatPtValid($vat);
        } elseif ($this->countryId === Countries::SPAIN) {
            $vat = strtoupper($vat);

            if (stripos($vat, 'ES') === 0) {
                $vat = str_ireplace('ES', '', $vat);
            }

            $isValid = Customer::isVatEsValid($vat);
        }

        if (!$isValid) {
            if (Context::settings()->getInt('vat_validate') === Boolean::YES) {
                return null;
            }

            throw new DocumentError(__('Customer has invalid VAT.', 'moloni-on'));
        }

        return substr($vat, 0, 30);
    }

    /**
     * Checks if the cohasmpany name is set
     * If they order  a company we issue the document to the company
     * And add the name of the person to the contact name
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        $billingName = $this->order->get_billing_first_name();
        $billingLastName = $this->order->get_billing_last_name();

        if (!empty($billingLastName)) {
            $billingName .= ' ' . $this->order->get_billing_last_name();
        }

        $billingCompany = trim($this->order->get_billing_company());

        if (!empty($billingCompany)) {
            $this->name = $billingCompany;
            $this->contactName = $billingName;
        } elseif (!empty($billingName)) {
            $this->name = $billingName;
        }

        return $this->name;
    }

    /**
     * Create a customer billing an address
     *
     * @return string
     */
    public function getCustomerBillingAddress(): string
    {
        $billingAddress = trim($this->order->get_billing_address_1());
        $billingAddress2 = $this->order->get_billing_address_2();

        if (!empty($billingAddress2)) {
            $billingAddress .= ' ' . trim($billingAddress2);
        }

        if (!empty($billingAddress)) {
            $this->address = $billingAddress;
        }

        return $this->address;
    }

    /**
     * Create a customer billing City
     *
     * @return string
     */
    public function getCustomerBillingCity(): string
    {
        $billingCity = trim($this->order->get_billing_city());

        if (!empty($billingCity)) {
            $this->city = $billingCity;
        }

        return $this->city;
    }

    /**
     * Gets the zip code of a customer
     * If the customer is Portuguese validate the Vat Number
     *
     * @return string
     */
    public function getCustomerZip(): string
    {
        $this->zipCode = $this->order->get_billing_postcode();

        return $this->zipCode;
    }

    //          Sets          //

    /**
     * Set language and country
     *
     * @throws DocumentError
     */
    private function setLanguageAndCountryId(): void
    {
        $countryCode = $this->order->get_billing_country();

        try {
            ['countryId' => $countryId, 'languageId' => $languageId] = Tools::getMoloniCountryByCode($countryCode);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching countries', 'moloni-on'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        $this->countryId = $countryId;
        $this->languageId = Context::settings()->getInt('customer_language') ?: $languageId;
    }

    //          Statics          //

    /**
     * Get the customer next available number for incremental inserts
     *
     * @return int
     */
    public static function getCustomerNextNumber()
    {
        $needle = Context::settings()->getString('client_prefix');
        $needle .= '%';

        $variables = [
            'options' => [
                'filter' => [
                    'field' => 'number',
                    'comparison' => 'like',
                    'value' => $needle
                ]
            ]
        ];

        $nextNumber = '';

        try {
            $query = Customers::queryCustomerNextNumber($variables);

            if (isset($query['data']['customerNextNumber']['data'])) {
                $nextNumber = $query['data']['customerNextNumber']['data'];
            }
        } catch (APIExeption $e) {
        }

        if (empty($nextNumber)) {
            $nextNumber = Context::settings()->getString('client_prefix');
            $nextNumber .= '1';
        }

        return $nextNumber;
    }

    //          Requests          //

    /**
     * Search for a customer based on $this->vat or $this->email
     *
     * @return bool|array
     *
     * @throws DocumentError
     */
    public function searchForCustomer()
    {
        $variables = [
            'options' => [
                'filter' => []
            ]
        ];

        if (empty($this->vat) && empty($this->email)) {
            return false;
        }

        if (empty($this->vat)) {
            $variables['options']['filter'][] = [
                'field' => 'email',
                'comparison' => 'eq',
                'value' => $this->email
            ];
        } else {
            $variables['options']['filter'][] = [
                'field' => 'vat',
                'comparison' => 'eq',
                'value' => $this->vat
            ];
        }

        try {
            $searchResult = Customers::queryCustomers($variables);
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching customers', 'moloni-on'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData()
                ]
            );
        }

        foreach ($searchResult['data']['customers']['data'] as $customer) {
            if (!empty($customer['vat'])) {
                continue;
            }

            return $customer;
        }

        return false;
    }
}
