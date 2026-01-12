<?php

namespace MoloniOn\Context;

use MoloniOn\Context;
use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $wpdb->insert(
            $tableName . '_logs',
            [
                'log_level' => $level,
                'company_id' => Context::$MOLONI_ON_COMPANY_ID ?? 0,
                'message' => $message,
                'context' => json_encode($context),
                'created_at' => gmdate('Y-m-d H:i:s')
            ]
        );
    }
}
