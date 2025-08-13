<?php

namespace MoloniOn\Services\Mails;

use MoloniOn\Services\Mails\Abstracts\MailAbstract;

class DocumentFailed extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni', 'moloni-on') . ' - ' . __('Moloni document error', 'moloni-on');
        $this->template = 'Emails/DocumentFailed.php';

        if (!empty($orderName)) {
            $this->extra = __('Order', 'moloni-on') . ': #' . $orderName;
        }

        $this->run();
    }
}
