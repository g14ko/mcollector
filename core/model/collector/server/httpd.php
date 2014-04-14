<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 3:46 PM
 */

namespace model\collector\server;

use lib\Model as model;
use \SimpleXMLElement as xml;

class HttpD extends model
{
    const TABLE = 'httpd';

    private static $data = [];

    public static function save(xml $server)
    {
        self::setPrimaryKey(self::$data);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), self::extractByXPath(self::TABLE, $server), self::$data);
    }

}