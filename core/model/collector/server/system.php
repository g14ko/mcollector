<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 5:34 PM
 */

namespace model\collector\server;

use \SimpleXMLElement as xml;
use lib\Model as model;
use model\Collector as collector;
use model\collector\server\system\Cpu as cpu;
use model\collector\server\system\Memory as memory;
use model\collector\server\system\Load as load;
use model\collector\server\system\Swap as swap;

class System extends model
{
    const TABLE = 'system';

    private static $data = [];

    private static $child = [
        'model\collector\server\system\Cpu'    => cpu::TABLE,
        'model\collector\server\system\Memory' => memory::TABLE,
        'model\collector\server\system\Load'   => load::TABLE,
        'model\collector\server\system\Swap'   => swap::TABLE
    ];

    public static function saveAll(array $systems)
    {
        self::cleanOldData(self::TABLE, self::UPDATE);
        foreach ($systems as $system)
        {
            self::save($system);
        }
    }

    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setForeignKeys(
            self::$data,
            self::saveChild(self::TABLE, self::$child, self::$data, self::extractByProperty(self::TABLE, $service))
        );
        self::setName(self::$data, $service);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    public static function addSelect($for, array &$select)
    {
        $select = array_merge($select, self::getSelect($for));
    }

    public static function getSelect($for, array $select = [])
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        self::setChildSelect($for, self::$child, $select, $fields);
        return array_merge(self::buildSelect(
                               self::TABLE,
                               $fields,
                               [
                                   [self::SERVER, collector::TABLE, self::ALIAS],
                                   [self::UPDATE, collector::TABLE, collector::START]
                               ]), $select);
    }

    public static function getGroup($for)
    {
        return [self::TABLE => self::config([$for, self::TABLE, self::GROUP])];
    }

    public static function getOrder($for)
    {
        return [self::TABLE => self::config([$for, self::TABLE, self::ORDER])];
    }

}