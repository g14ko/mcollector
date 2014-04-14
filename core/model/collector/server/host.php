<?php
/**
 * @author eugene
 * @date 1/15/14
 * @time 12:36 PM
 */

namespace model\collector\server;

use lib\Model as model;
use model\collector\server\host\Port as port;
use \SimpleXMLElement as xml;

class Host extends model
{
    const TABLE = 'host';

    private static $data = [];

    private static $child = [
        'model\collector\server\host\Port' => port::TABLE
    ];

    private static $statuses = [
        self::STATUS_RUNNING => 'online with all services', // todo statuses
        self::STATUS_DOES_NOT_EXISTS => 'not monitored',
        32 => 'unknown'
    ];

    public static function saveAll(array $hosts)
    {
        self::cleanOldData(self::TABLE, self::UPDATE);
        foreach ($hosts as $host)
        {
            self::save($host);
        }
    }

    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setForeignKeys(self::$data, self::saveChild(self::TABLE, self::$child, self::$data, $service));
        self::setName(self::$data, $service);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    public static function getStatus($status)
    {
        return self::$statuses[$status];
    }

    public static function getSelect($for, array $select = [])
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        self::setChildSelect($for, self::$child, $select, $fields);
        return array_merge(self::buildSelect(self::TABLE, $fields), $select);
    }

}