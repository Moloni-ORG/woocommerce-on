<?php

namespace MoloniOn\Services\Mails;

use MoloniOn\Services\Mails\Abstracts\MailAbstract;

class DocumentWarning extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni ON', 'moloni-on') . ' - ' . __('Moloni ON document warning', 'moloni-on');
        $this->template = 'Emails/DocumentWarning.php';

        if (!empty($orderName)) {
            $this->extra = __('Order', 'moloni-on') . ': #' . $orderName;
        }

        $this->run();
    }
}
