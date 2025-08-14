<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.ExceptionNotEscaped

namespace MoloniOn;

use MoloniOn\Exceptions\Core\MoloniException;

final class Configurations
{

    private $configs = [];

    /**
     * Constructor for the configuration data
     *
     * @throws MoloniException
     */
    public function __construct($env = null)
    {
        if (empty($env)) {
            $env = parse_ini_file(MOLONI_ON_DIR . '/.env');
        }

        $platform = $env['PLATFORM'] ?? '';
        $isDev = $env['IS_DEV'] ?? false;

        if ($isDev) {
            $configFile = MOLONI_ON_DIR . "/.platforms/$platform/config/platform.php";
        } else {
            $configFile = MOLONI_ON_DIR . "/config/platform.php";
        }

        if (!file_exists($configFile)) {
            throw new MoloniException("Configuration file for platform '$platform' not found.");
        }

        $configs = require $configFile;

        if (!is_array($configs)) {
            throw new MoloniException('Invalid configuration file format.');
        }

        $this->configs = array_merge($configs, ['platform' => $platform, 'is_dev' => $isDev]);
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
