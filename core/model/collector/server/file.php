<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 5:27 PM
 */

namespace model\collector\server;

use lib\Model as model;
use \SimpleXMLElement as xml;

class File extends model
{
    const TABLE = 'file';

    private static $data = [];

    private static $statuses = [
        self::STATUS_RUNNING => 'accessible',
        self::STATUS_DOES_NOT_EXISTS => 'not accessible'
    ];

    public static function saveAll(array $files)
    {
        self::cleanOldData(self::TABLE, self::UPDATE);
        foreach ($files as $file)
        {
            self::save($file);
        }
    }

    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setName(self::$data, $service);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    public static function getSelect($for)
    {
        return self::buildSelect(self::TABLE, self::config([$for, self::TABLE, self::SELECT]));
    }

    public static function getStatus($status)
    {
        return self::$statuses[$status];
    }

}