<?php
/**
 * User: eugene
 * Date: 4/16/14
 * Time: 10:09 AM
 */

namespace component;

use \SimpleXMLElement as xml;

trait Model
{

    public static function saveAll(array $services)
    {
        foreach ($services as $service)
        {
            self::save($service);
        }
    }

    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setName(self::$data, $service);
        self::setForeignKeys(self::$data, self::saveChild(self::TABLE, self::$child, self::$data, $service));
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    public static function addSelect($for, array &$select)
    {
        $select = array_merge($select, self::getSelect($for));
    }

    public static function getSelect($for, array $select = [])
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        if (empty($fields))
        {
            throw new \Exception('not specified fields for selection in the table ' . self::TABLE);
        }
        if (property_exists(__CLASS__, 'child'))
        {
            self::setChildSelect($for, self::$child, $select, $fields);
        }
        return array_merge(self::buildSelect(self::TABLE, $fields), $select);
    }

    public static function getStatus($status)
    {
        switch (true)
        {
            case !property_exists(__CLASS__, 'statuses'):
                throw new \Exception('not set statuses in ' . implode('\\\\', explode('\\', __CLASS__)));
            case !isset(self::$statuses[$status]):
                throw new \Exception('is not set current status (' . $status . ') in ' . implode('\\\\', explode('\\', __CLASS__)));
        }
        return self::$statuses[$status];
    }

}