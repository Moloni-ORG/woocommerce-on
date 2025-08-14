<?php

use MoloniOn\Plugin;
use MoloniOn\Context;
use MoloniOn\Exceptions\Core\MoloniException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Variables
 *
 * @var $this Plugin
 */
?>

<section id="moloni" class="moloni">
    <div class="header">
        <img src="<?php echo esc_url(Context::getImagesPath()) ?>logo.svg" width='300px' alt="Moloni">
    </div>

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo esc_url(Context::getAdminUrl()) ?>"
           class="nav-tab <?php echo ($this->activeTab === '') ? 'nav-tab-active' : '' ?>">
            <?php esc_html_e('Orders', 'moloni-on') ?>
        </a>

        <a href="<?php echo esc_url(Context::getAdminUrl('tab=settings')) ?>"
           class="nav-tab <?php echo ($this->activeTab === 'settings') ? 'nav-tab-active' : '' ?>">
            <?php esc_html_e('Settings', 'moloni-on') ?>
        </a>

        <a href="<?php echo esc_url(Context::getAdminUrl('tab=automation')) ?>"
           class="nav-tab <?php echo ($this->activeTab === 'automation') ? 'nav-tab-active' : '' ?>">
            <?php esc_html_e('Automation', 'moloni-on') ?>
        </a>

        <a href="<?php echo esc_url(Context::getAdminUrl('tab=logs')) ?>"
           class="nav-tab <?php echo $this->activeTab === 'logs' ? 'nav-tab-active' : '' ?>">
            <?php esc_html_e('Logs', 'moloni-on') ?>
        </a>

        <a href="<?php echo esc_url(Context::getAdminUrl('tab=tools')) ?>"
           class="nav-tab <?php echo (in_array($this->activeTab, ['tools', 'wcProductsList', 'moloniProductsList'])) ? 'nav-tab-active' : '' ?>">
            <?php esc_html_e('Tools', 'moloni-on') ?>
        </a>
    </nav>

    <div class="moloni__container">
        <?php

        if (isset($pluginErrorException) && $pluginErrorException instanceof MoloniException) {
            $pluginErrorException->showError();
        }

        switch ($this->activeTab) {
            case 'tools':
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/Tools.php';
                break;
            case 'automation':
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/Automation.php';
                break;
            case 'settings':
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/Settings.php';
                break;
            case 'logs':
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/Logs.php';
                break;
            case 'wcProductsList':
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/WcProducts.php';
                break;
            case 'moloniProductsList':
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/MoloniProducts.php';
                break;
            default:
                include MOLONI_ON_TEMPLATE_DIR . 'Containers/PendingOrders.php';
                break;
        }
        ?>
    </div>
</section>
