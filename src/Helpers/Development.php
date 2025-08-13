<?php

namespace MoloniOn\Helpers;

use MoloniOn\Configurations;

class Development
{
    /**
     * Cached configurations for all platforms
     *
     * @var Configurations[]
     */
    private static $cachedConfigs = [];

    public static function getPlatformsConfigurations(): array
    {
        if (!empty(self::$cachedConfigs)) {
            return self::$cachedConfigs;
        }

        $directoryPath = MOLONI_ON_DIR . '/.platforms';

        $items = scandir($directoryPath) ?? [];

        $folders = [];

        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($directoryPath . DIRECTORY_SEPARATOR . $item)) {
                $folders[] = $item;
            }
        }

        foreach ($folders as $folder) {
            self::$cachedConfigs[] = new Configurations(['PLATFORM' => $folder, 'IS_DEV' => true]);
        }

        return self::$cachedConfigs;
    }

    public static function getPlatformsTableNames($blogId = null): array
    {
        global $wpdb;

        $platforms = self::getPlatformsConfigurations();

        $prefix = $wpdb->get_blog_prefix($blogId);

        $tableNames = [];

        foreach ($platforms as $platform) {
            $tableNames[] = "{$prefix}moloni_{$platform->get('database_infix')}";
        }

        return $tableNames;
    }
}
