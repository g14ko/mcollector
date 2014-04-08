<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 3:34 PM
 */

namespace model\collector;

use lib\Model as model;

class Platform extends model
{
    const TABLE = 'platform';

    private static $data = [];

    public static function save()
    {
        self::setPrimaryKey(self::$data);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), self::extractByXPath(self::TABLE), self::$data);
    }

}