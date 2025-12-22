<?php

namespace MoloniOn\Enums;

class DocumentTypes
{
    public const INVOICE = 'invoice';
    public const RECEIPT = 'receipt';
    public const INVOICE_RECEIPT = 'invoiceReceipts';
    public const SIMPLIFIED_INVOICE = 'simplifiedInvoice';
    public const BILLS_OF_LADING = 'billsOfLading';
    public const PURCHASE_ORDER = 'purchaseOrder';
    public const ESTIMATE = 'estimate';
    public const PRO_FORMA_INVOICE = 'proFormaInvoice';

    public const TYPES_WITH_PAYMENTS = [
        self::RECEIPT,
        self::INVOICE_RECEIPT,
        self::PRO_FORMA_INVOICE,
        self::SIMPLIFIED_INVOICE,
    ];

    public const TYPES_WITH_DELIVERY = [
        self::INVOICE,
        self::INVOICE_RECEIPT,
        self::PURCHASE_ORDER,
        self::PRO_FORMA_INVOICE,
        self::SIMPLIFIED_INVOICE,
        self::ESTIMATE,
        self::BILLS_OF_LADING,
    ];

    public const TYPES_REQUIRES_DELIVERY = [
        self::BILLS_OF_LADING,
    ];

    public const TYPES_RELATES_TO_BILL_OF_LADING = [
        self::INVOICE,
        self::INVOICE_RECEIPT,
        self::SIMPLIFIED_INVOICE,
        self::PURCHASE_ORDER,
        self::PRO_FORMA_INVOICE,
    ];

    public const TYPES_WITH_PRODUCTS = [
        self::INVOICE,
        self::INVOICE_RECEIPT,
        self::PURCHASE_ORDER,
        self::PRO_FORMA_INVOICE,
        self::SIMPLIFIED_INVOICE,
        self::ESTIMATE,
        self::BILLS_OF_LADING,
    ];

    public static function getForRender(): array
    {
        return [
            self::INVOICE => __('Invoice', 'moloni-on'),
            self::INVOICE_RECEIPT => __('Invoice-Receipt', 'moloni-on'),
            self::PURCHASE_ORDER => __('Purchase Order', 'moloni-on'),
            self::PRO_FORMA_INVOICE => __('Pro Forma Invoice', 'moloni-on'),
            self::SIMPLIFIED_INVOICE => __('Simplified invoice', 'moloni-on'),
            self::ESTIMATE => __('Budget', 'moloni-on'),
            self::BILLS_OF_LADING => __('Bills of lading', 'moloni-on')
        ];
    }

    public static function getDocumentTypeName(?string $documentType = ''): string
    {
        switch ($documentType) {
            case self::INVOICE:
                return __('Invoice', 'moloni-on');
            case self::RECEIPT:
                return __('Receipt', 'moloni-on');
            case self::INVOICE_RECEIPT:
                return __('Invoice-Receipt', 'moloni-on');
            case self::PURCHASE_ORDER:
                return __('Purchase Order', 'moloni-on');
            case self::PRO_FORMA_INVOICE:
                return __('Pro Forma Invoice', 'moloni-on');
            case self::SIMPLIFIED_INVOICE:
                return __('Simplified Invoice', 'moloni-on');
            case self::ESTIMATE:
                return __('Budget', 'moloni-on');
            case self::BILLS_OF_LADING:
                return __('Bill of lading', 'moloni-on');
        }

        return $documentType;
    }

    public static function hasPayments(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_PAYMENTS, true);
    }

    public static function hasProducts(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_PRODUCTS, true);
    }

    public static function hasDelivery(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_WITH_DELIVERY, true);
    }

    public static function requiresDelivery(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_REQUIRES_DELIVERY, true);
    }

    public static function canRelateToBillOfLading(?string $documentType = ''): bool
    {
        return in_array($documentType, self::TYPES_RELATES_TO_BILL_OF_LADING, true);
    }
}
