<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 5:28 PM
 */

namespace model\collector\server;

use lib\Model as model;
use \SimpleXMLElement as xml;

class Directory extends model
{
    use \component\Model;

    const TABLE = 'directory';

    private static $data = [];

    private static $statuses = [
        self::STATUS_RUNNING => 'accessible',
        self::STATUS_DOES_NOT_EXISTS => 'not accessible'
    ];

    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setName(self::$data, $service);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

}