<?php

namespace MoloniOn\Traits;

use MoloniOn\Context;
use MoloniOn\Enums\Boolean;

trait SyncFieldsSettingsTrait
{
    protected function productShouldSyncEAN(): bool
    {
        return Context::settings()->getInt('sync_fields_ean') === Boolean::YES;
    }

    protected function productShouldSyncCategories(): bool
    {
        return Context::settings()->getInt('sync_fields_categories') === Boolean::YES;
    }

    protected function productShouldSyncStock(): bool
    {
        if (!Context::settings()->getInt('sync_fields_stock')) {
            return false;
        }

        return Context::company()->canSyncStock();
    }

    protected function productShouldSyncVisibility(): bool
    {
        return Context::settings()->getInt('sync_fields_visibility') === Boolean::YES;
    }

    protected function productShouldSyncImage(): bool
    {
        return Context::settings()->getInt('sync_fields_image') === Boolean::YES;
    }

    protected function productShouldSyncPrice(): bool
    {
        return Context::settings()->getInt('sync_fields_price') === Boolean::YES;
    }

    protected function productShouldSyncDescription(): bool
    {
        return Context::settings()->getInt('sync_fields_description') === Boolean::YES;
    }

    protected function productShouldSyncName(): bool
    {
        return Context::settings()->getInt('sync_fields_name') === Boolean::YES;
    }
}
