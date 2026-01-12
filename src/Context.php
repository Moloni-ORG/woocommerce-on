<?php

namespace MoloniOn;

use MoloniOn\Context\Company;
use MoloniOn\Context\Configurations;
use MoloniOn\Context\Logger;
use MoloniOn\Context\Settings;
use MoloniOn\Helpers\External;
use MoloniOn\Helpers\Security;
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

    /**
     * Company instance information
     *
     * @var Company|null
     */
    private static $COMPANY;

    /**
     * Company instance information
     *
     * @var Settings|null
     */
    private static $SETTINGS;

    public static function initContext()
    {
        self::$USES_NEW_ORDERS_SYSTEM = External::isNewOrdersSystemEnabled();

        self::$LOGGER = new Logger();
        self::$CONFIGURATIONS = new Configurations();
        self::$COMPANY = null;

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

    public static function company(): ?Company
    {
        return self::$COMPANY;
    }

    public static function settings(): ?Settings
    {
        return self::$SETTINGS;
    }

    // Sets //

    public static function setSettings(?array $settings = [])
    {
        self::$SETTINGS = new Settings($settings);
    }

    public static function setCompany(?array $company = [])
    {
        self::$COMPANY = new Company($company);
    }

    // Gets //

    public static function getPageName(): string
    {
        return self::configs()->get('page_name') ?? '';
    }

    public static function getImagesPath(): string
    {
        return MOLONI_ON_PLUGIN_URL . 'images/';
    }

    public static function getTableName($blogId = null): string
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix($blogId);

        return "{$prefix}moloni_on";
    }

    public static function getAdminUrl(?string $arguments = ''): string
    {
        $pageName = self::getPageName();
        $arguments = ltrim($arguments, '&');

        return Security::get_nonce_url(admin_url("admin.php?page=$pageName&$arguments"));
    }
}
