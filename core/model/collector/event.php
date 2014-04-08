<?php
/**
 * @author eugene
 * @date 2/6/14
 * @time 4:12 PM
 */

namespace model\collector;

use lib\Model as model;
use \SimpleXMLElement as xml;

class Event extends model
{
    const TABLE = 'event';

    private static $select = [
        'message' => '',
        'service' => ''
    ];

    private static $data = [];

    public static function save(xml $event)
    {
        self::setPrimaryKey(self::$data);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $event, self::$data);
    }

    public static function getSelect()
    {
        return self::buildSelect(self::TABLE, self::$select);
    }

}