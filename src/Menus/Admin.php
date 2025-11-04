<?php

namespace MoloniOn\Menus;

use MoloniOn\Context;
use MoloniOn\Helpers\Security;
use MoloniOn\Plugin;

class Admin
{

    /** @var int This should be the same as WooCommerce to keep menus together */
    private $menuPosition = 56;

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        add_action('admin_menu', [$this, 'admin_menu'], $this->menuPosition);
        add_action('admin_notices', '\MoloniOn\Notice::showMessages');
    }

    public function admin_menu()
    {
        if (!Security::verify_user_can_access_wc()) {
            return;
        }

        $pageName = Context::configs()->get('name_translated');
        $menuSlug = Context::getPageName();
        $logoUrl = Context::getImagesPath() . 'small_logo.png';

        add_menu_page(
            $pageName,
            $pageName,
            'manage_woocommerce',
            $menuSlug,
            [$this->parent, 'run'],
            $logoUrl,
            $this->menuPosition
        );
    }
}
