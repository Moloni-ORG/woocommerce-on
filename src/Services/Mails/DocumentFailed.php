<?php

namespace MoloniOn\Services\Mails;

use MoloniOn\Services\Mails\Abstracts\MailAbstract;

class DocumentFailed extends MailAbstract
{
    public function __construct($to = '', $orderName = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni', 'moloni_on') . ' - ' . __('Moloni document error', 'moloni_on');
        $this->template = 'Emails/DocumentFailed.php';

        if (!empty($orderName)) {
            $this->extra = __('Order', 'moloni_on') . ': #' . $orderName;
        }

        $this->run();
    }
}
