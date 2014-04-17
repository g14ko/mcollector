<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 5:08 PM
 */

namespace model\collector\server\filesystem;

use lib\Model as model;
use model\collector\server\FileSystem as filesystem;
use \SimpleXMLElement as xml;

class Inode extends model
{
    use \component\Model;

    const PARENT = 'filesystem';
    const TABLE = 'inode';

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