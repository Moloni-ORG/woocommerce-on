<?php

namespace MoloniOn\Services\Mails;

use MoloniOn\Services\Mails\Abstracts\MailAbstract;

class DocumentWarning extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni', 'moloni_on') . ' - ' . __('Moloni document warning', 'moloni_on');
        $this->template = 'Emails/DocumentWarning.php';

        if (!empty($orderName)) {
            $this->extra = __('Order', 'moloni_on') . ': #' . $orderName;
        }

        $this->run();
    }
}
