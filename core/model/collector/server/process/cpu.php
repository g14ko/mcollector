<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 12:40 PM
 */

namespace model\collector\server\process;

use lib\Model as model;
use model\collector\server\Process as process;
use \SimpleXMLElement as xml;

class Cpu extends model
{
    const TABLE = 'cpu';

    private static $data = [];

    public static function save($id, xml $service)
    {
        self::setId(self::$data, $id);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), self::extractByProperty(self::TABLE, $service), self::$data);
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
                   [[self::ID, process::TABLE, self::getNameId(self::TABLE)]]
        );
    }

}