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

    const PARENT = 'host';
    const TABLE = 'port';

    private static $data = [];

    public static function save($id, xml $service)
    {
        self::childSave($id, $service);
    }

    public static function getSelect($for)
    {
        return self::getChildSelect($for);
    }

}