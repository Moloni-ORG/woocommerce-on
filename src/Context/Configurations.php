<?php

namespace MoloniOn\Context;

use MoloniOn\Exceptions\Core\MoloniException;

final class Configurations
{

    private $configs = [];

    /**
     * Constructor for the configuration data
     *
     * @throws MoloniException
     */
    public function __construct()
    {
        $configFile = MOLONI_ON_DIR . "/config/platform.php";

        if (!file_exists($configFile)) {
            throw new MoloniException("Configuration file for not found.");
        }

        $configs = require $configFile;

        if (!is_array($configs)) {
            throw new MoloniException('Invalid configuration file format.');
        }

        $this->configs = $configs;
    }

    public function get(string $key)
    {
        return $this->configs[$key] ?? null;
    }

    public function getAll(): array
    {
        return $this->configs;
    }
}
