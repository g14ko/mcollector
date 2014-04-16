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

    const PARENT = 'process';
    const TABLE = 'memory';

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