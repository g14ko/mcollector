<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 5:03 PM
 */

namespace model\collector\server\filesystem;

use lib\Model as model;
use model\collector\server\FileSystem as filesystem;
use \SimpleXMLElement as xml;

class Block extends model
{
    use \component\Model;

    const PARENT = 'filesystem';
    const TABLE = 'block';

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