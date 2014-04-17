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
    use \component\Model;

    const PARENT = 'process';
    const TABLE = 'cpu';

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