<?php

namespace MoloniOn\Context;

final class Settings
{
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function getString(string $key, $default = ''): string
    {
        return (string)($this->get($key, $default));
    }

    public function getInt(string $key, $default = 0): int
    {
        return (int)($this->get($key, $default));
    }

    public function getAll(): array
    {
        return $this->settings;
    }
}
