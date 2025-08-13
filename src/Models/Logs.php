<?php

namespace MoloniOn\Models;

use MoloniOn\Context;

class Logs
{
    private static $limit = 10;
    private static $totalPages = 1;
    private static $currentPage = 1;

    private static $filterDate = '';
    private static $filterMessage = '';
    private static $filterLevel = '';

    public static function getAllAvailable(): array
    {
        self::$currentPage = (isset($_GET['paged']) && (int)($_GET['paged']) > 0) ? (int)$_GET['paged'] : 1;

        self::$filterDate = sanitize_text_field($_GET['filter_date'] ?? $_POST['filter_date'] ?? '');
        self::$filterMessage = sanitize_text_field($_GET['filter_message'] ?? $_POST['filter_message'] ?? '');
        self::$filterLevel = sanitize_text_field($_GET['filter_level'] ?? $_POST['filter_level'] ?? '');

        return self::getAll();
    }

    public static function getPagination()
    {
        $baseArguments = add_query_arg([
            'paged' => '%#%',
            'filter_date' => self::$filterDate,
            'filter_message' => self::$filterMessage,
            'filter_level' => self::$filterLevel,
        ]);

        $args = [
            'base' => $baseArguments,
            'format' => '',
            'current' => self::$currentPage,
            'total' => self::$totalPages,
        ];

        return paginate_links($args);
    }

    public static function removeOlderLogs()
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $query = $wpdb->prepare(
            "DELETE FROM `{$tableName}_logs` WHERE created_at < %s",
            gmdate('Y-m-d H:i:s', strtotime("-1 week"))
        );

        $wpdb->query($query);
    }

    //           Privates           //

    /**
     * Fetch logs
     *
     * @return array
     */
    private static function getAll(): array
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $limit = self::$limit;
        $offset = self::$currentPage <= 1 ? 0 : (self::$currentPage - 1) * self::$limit;

        /** Totals */

        $query = "SELECT COUNT(*) FROM `{$tableName}_logs`";
        $arguments = [];

        self::applyFilters($query, $arguments);

        $queryClean = $wpdb->prepare($query, ...$arguments);
        $queryResult = $wpdb->get_row($queryClean, ARRAY_A);

        $numLogs = (int)($queryResult['COUNT(*)'] ?? 0);

        /** Can safely return if there are no logs */
        if ($numLogs === 0) {
            return [];
        }

        self::$totalPages = ceil($numLogs / self::$limit);

        /** Results */

        $query = "SELECT * FROM `{$tableName}_logs`";
        $arguments = [];

        self::applyFilters($query, $arguments);

        $query .= ' ORDER BY id DESC LIMIT %d OFFSET %d';
        $arguments[] = $limit;
        $arguments[] = $offset;

        $queryClean = $wpdb->prepare($query, ...$arguments);

        return $wpdb->get_results($queryClean, ARRAY_A);
    }

    //           Auxiliary           //

    private static function applyFilters(&$sql, &$arguments)
    {
        $sql .= ' WHERE (company_id = %d OR company_id = %d)';
        $arguments[] = Context::$MOLONI_ON_COMPANY_ID ?? 0;
        $arguments[] = 0;

        if (!empty(self::$filterMessage)) {
            $sql .= ' AND message LIKE %s';
            $arguments[] = '%' . self::$filterMessage . '%';
        }

        if (!empty(self::$filterLevel)) {
            $sql .= ' AND log_level = %s';
            $arguments[] = self::$filterLevel;
        }

        if (!empty(self::$filterDate)) {
            $sql .= ' AND created_at LIKE %s';
            $arguments[] = self::$filterDate . '%';
        }
    }
}
