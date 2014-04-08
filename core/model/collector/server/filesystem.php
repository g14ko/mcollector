<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 4:16 PM
 */

namespace model\collector\server;

use lib\Model as model;
use model\collector\server\filesystem\Block as block;
use model\collector\server\filesystem\Inode as inode;
use \SimpleXMLElement as xml;

class FileSystem extends model
{
    const TABLE = 'filesystem';

    private static $data = [];

    private static $child = [
        'model\collector\server\filesystem\Block' => block::TABLE,
        'model\collector\server\filesystem\Inode' => inode::TABLE
    ];

    private static $statuses = [
        self::STATUS_RUNNING => 'accessible',
        self::STATUS_DOES_NOT_EXISTS => 'not accessible'
    ];

    public static function saveAll(array $filesystems)
    {
        self::cleanOldData(self::TABLE, self::UPDATE);
        foreach ($filesystems as $filesystem)
        {
            self::save($filesystem);
        }
    }

    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setName(self::$data, $service);
        self::setForeignKeys(self::$data, self::saveChild(self::TABLE, self::$child, self::$data, $service));
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    public static function getSelect($for, array $select = [])
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        self::setChildSelect($for, self::$child, $select, $fields);
        return array_merge(self::buildSelect(self::TABLE, $fields), $select);
    }

    public static function getStatus($status)
    {
        return self::$statuses[$status];
    }

}