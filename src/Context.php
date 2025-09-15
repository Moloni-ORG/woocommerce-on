<?php

namespace MoloniOn;

use MoloniOn\Helpers\External;
use MoloniOn\Helpers\Security;
use MoloniOn\Tools\Logger;
use Psr\Log\LoggerInterface;

class Context
{
    public static $MOLONI_ON_SESSION_ID;
    public static $MOLONI_ON_ACCESS_TOKEN;
    public static $MOLONI_ON_COMPANY_ID;

    /**
     * Checks if a new order system is being used
     *
     * @var bool
     */
    public static $USES_NEW_ORDERS_SYSTEM = false;

    /**
     * Logger instance
     *
     * @var LoggerInterface|null
     */
    private static $LOGGER;

    /**
     * Configuration instance
     *
     * @var Configurations|null
     */
    private static $CONFIGURATIONS;

    public static function initContext()
    {
        self::$USES_NEW_ORDERS_SYSTEM = External::isNewOrdersSystemEnabled();

        self::$LOGGER = new Logger();
        self::$CONFIGURATIONS = new Configurations();

        self::resetSession();
    }

    public static function resetSession()
    {
        self::$MOLONI_ON_SESSION_ID = '';
        self::$MOLONI_ON_ACCESS_TOKEN = '';
        self::$MOLONI_ON_COMPANY_ID = '';
    }

    // Dependencies //

    public static function logger(): ?LoggerInterface
    {
        return self::$LOGGER;
    }

    public static function configs(): ?Configurations
    {
        return self::$CONFIGURATIONS;
    }

    // Gets //

    public static function getPageName(): string
    {
        return self::configs()->get('page_name') ?? '';
    }

    public static function getCssPath(): string
    {
        return self::configs()->get('is_dev') ? ".platforms/" . self::configs()->get('platform') : 'assets';
    }

    public static function getImagesPath(): string
    {
        if (self::configs()->get('is_dev')) {
            return MOLONI_ON_PLUGIN_URL . '.platforms/' . self::configs()->get('platform') . '/images/';
        }

        return MOLONI_ON_PLUGIN_URL . 'images/';
    }

    public static function getTableName($blogId = null): string
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix($blogId);
        $infix = self::configs()->get('database_infix');

        return "{$prefix}moloni_{$infix}";
    }

    public static function getAdminUrl(?string $arguments = ''): string
    {
        $pageName = self::getPageName();
        $arguments = ltrim($arguments, '&');

        return Security::get_nonce_url(admin_url("admin.php?page=$pageName&$arguments"));
    }
}
