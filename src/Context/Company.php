<?php

namespace MoloniOn\Context;

final class Company
{
    private $company;

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
        foreach ($this->company['limits'] ?? [] as $key => $value) {
            if (in_array($value['moduleId'], $this->targetPermissions)) {
                continue;
            }

            unset($this->company['limits'][$key]);
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

    // Privates //

    private function isAllowed(string $resource): bool
    {
        $limits = $this->company['limits'] ?? [];

        foreach ($limits as $limit) {
            if ($limit['moduleId'] !== $resource) {
                continue;
            }

            return $limit['active'] === true;
        }

        return false;
    }
}
