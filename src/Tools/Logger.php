<?php

namespace MoloniOn\Tools;

use MoloniOn\Context;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $query = $wpdb->prepare(
            "INSERT INTO `{$tableName}_logs`(log_level, company_id, message, context, created_at) VALUES(%s, %d, %s, %s, %s)",
            $level,
            Context::$MOLONI_ON_COMPANY_ID ?? 0,
            $message,
            json_encode($context),
            date('Y-m-d H:i:s')
        );

        $wpdb->query($query);
    }
}
