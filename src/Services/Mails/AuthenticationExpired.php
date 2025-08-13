<?php

namespace MoloniOn\Services\Mails;

use MoloniOn\Services\Mails\Abstracts\MailAbstract;

class AuthenticationExpired extends MailAbstract
{
    public function __construct($to = '')
    {
        $this->to = $to;
        $this->subject = __('Plugin Moloni', 'moloni-on') . ' - ' . __('The Moloni authentication expired', 'moloni-on');
        $this->template = 'Emails/AuthenticationExpired.php';

        $this->run();
    }
}
