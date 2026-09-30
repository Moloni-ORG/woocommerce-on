<?php

namespace MoloniOn\Context;

final class Company
{
    private $company;

    /**
     * The plan's product count limit (the limits entry with this resource). Moloni ON refuses
     * a product create once its "remaining" reaches 0.
     */
    private const PRODUCTS_RESOURCE = 'products';

    private $targetPermissions = [
        'plugins.woocommerce',
        'tools.apiClients',
        'tools.webhooks',
        'productsServices.productProperties',
        'productsServices.stocks',
        'productsServices.warehouses',
    ];

    public function __construct(array $company)
    {
        foreach ($company['limits'] ?? [] as $key => $value) {
            if (in_array($value['moduleId'] ?? null, $this->targetPermissions)) {
                continue;
            }

            if (($value['resource'] ?? null) === self::PRODUCTS_RESOURCE) {
                continue;
            }

            unset($company['limits'][$key]);
        }

        $this->company = $company;
    }

    // Gets //

    public function get(string $key)
    {
        return $this->company[$key] ?? null;
    }

    public function getAll(): array
    {
        return $this->company;
    }

    public function getCompanyId(): int
    {
        return (int)$this->company['companyId'];
    }

    public function getCountry(): int
    {
        return (int)$this->company['country']['countryId'];
    }

    /**
     * Whether the company is Portuguese (country ISO code "pt", case-insensitive). Some
     * fiscal rules (e.g. blocking a product name change once it has documents) only apply
     * to Portuguese companies.
     */
    public function isPT(): bool
    {
        return strtolower($this->company['country']['iso3166_1'] ?? '') === 'pt';
    }

    // Permissions //

    public function hasPlugin(): bool
    {
        return $this->isAllowed('plugins.woocommerce');
    }

    public function hasApiClient(): bool
    {
        return $this->isAllowed('tools.apiClients');
    }

    public function hasWebhooks(): bool
    {
        return $this->isAllowed('tools.webhooks');
    }

    public function hasProperties(): bool
    {
        return $this->isAllowed('productsServices.productProperties');
    }

    public function hasStocks(): bool
    {
        return $this->isAllowed('productsServices.stocks');
    }

    public function hasWarehouses(): bool
    {
        return $this->isAllowed('productsServices.warehouses');
    }

    public function canSyncStock(): bool
    {
        return $this->hasStocks() && $this->hasWarehouses();
    }

    // Limits //

    /**
     * Whether the plan still has room for another product. No products entry (unexpected)
     * means don't block, and let Moloni ON decide.
     */
    public function canCreateProducts(): bool
    {
        foreach ($this->company['limits'] ?? [] as $limit) {
            if (($limit['resource'] ?? null) !== self::PRODUCTS_RESOURCE) {
                continue;
            }

            return (int)($limit['remaining'] ?? 0) > 0;
        }

        return true;
    }

    // Privates //

    private function isAllowed(string $resource): bool
    {
        $limits = $this->company['limits'] ?? [];

        foreach ($limits as $limit) {
            if (($limit['moduleId'] ?? null) !== $resource) {
                continue;
            }

            return $limit['active'] === true;
        }

        return false;
    }
}
