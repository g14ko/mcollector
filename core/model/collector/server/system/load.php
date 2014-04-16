<?php
/**
 * User: j3nya
 * Date: 07.09.13
 * Time: 19:53
 */

namespace model\collector\server\system;

use lib\Model as model;
use model\collector\server\System as system;
use \SimpleXMLElement as xml;

class Load extends model
{
    use \component\Model;

    const PARENT = 'system';
    const TABLE = 'systemload';

    private static $data = [];

    public static function save($id, xml $service)
    {
        self::childSave($id, $service, self::cutSystemChildTableName(self::TABLE));
    }

    public static function getSelect($for)
    {
        return self::getChildSelect($for);
    }

}