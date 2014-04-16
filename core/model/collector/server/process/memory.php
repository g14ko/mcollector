<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 5:41 PM
 */

namespace model\collector\server\process;

use lib\Model as model;
use model\collector\server\Process as process;
use \SimpleXMLElement as xml;

class Memory extends model
{
    use \component\Model;

    const TABLE = 'memory';

    private static $data = [];

    public static function save($id, xml $service)
    {
        self::setId(self::$data, $id);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), self::extractByProperty(self::TABLE, $service), self::$data);
    }

    public static function getSelect($for)
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        return self::buildSelect(self::TABLE, $fields, [[self::ID, process::TABLE, self::getNameId(self::TABLE)]]);
    }

}