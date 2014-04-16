<?php
/**
 * @author eugene
 * @date 1/15/14
 * @time 12:36 PM
 */

namespace model\collector\server\host;

use lib\Model as model;
use model\collector\server\Host as host;
use \SimpleXMLElement as xml;

class Port extends model
{
    use \component\Model;

    const TABLE = 'port';

    private static $data = [];

    public static function save($id, xml $service)
    {
        self::setId(self::$data, $id);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), self::extractByProperty(self::TABLE, $service), self::$data);
    }

    public static function getSelect($for)
    {
        return self::buildSelect(
                   self::TABLE,
                   self::config([$for, self::TABLE, self::SELECT]),
                   [[self::ID, host::TABLE, self::getNameId(self::TABLE)]]
        );
    }

}