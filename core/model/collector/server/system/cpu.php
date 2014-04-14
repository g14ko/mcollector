<?php
/**
 * User: j3nya
 * Date: 07.09.13
 * Time: 17:30
 */

namespace model\collector\server\system;

use lib\Model as model;
use model\collector\server\System as system;
use \SimpleXMLElement as xml;

class Cpu extends model
{
    const TABLE = 'systemcpu';

    private static $data = [];

    public static function save($id, xml $service)
    {
        self::setId(self::$data, $id);
        self::saveToDB(
            self::TABLE,
            self::getFields(self::SAVE, self::TABLE),
            self::extractByProperty(self::cutSystemChildTableName(self::TABLE), $service),
            self::$data
        );
    }

    public static function addSelect($for, array &$select)
    {
        $select = array_merge($select, self::getSelect($for));
    }

    private static function getSelect($for)
    {
        return self::buildSelect(
                   self::TABLE,
                   self::config([$for, self::TABLE, self::SELECT]),
                   [[self::ID, system::TABLE, self::getNameId(self::TABLE)]]
        );
    }

}