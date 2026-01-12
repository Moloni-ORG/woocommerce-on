<?php

namespace MoloniOn\Traits;

use MoloniOn\Context;

trait SettingsTrait
{
    protected function isSyncProductWithVariantsActive(): bool
    {
        return Context::company()->hasProperties();
    }
}
