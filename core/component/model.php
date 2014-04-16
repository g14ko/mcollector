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
    /**
     * Сохранить сервисы
     *
     * @param array $services Массив с сервисами
     * @return void
     */
    public static function saveAll(array $services)
    {
        foreach ($services as $service)
        {
            self::save($service);
        }
    }

    /**
     * Сохранить сервис
     *
     * @param xml $service Сервис
     * @return void
     */
    public static function save(xml $service)
    {
        self::setPrimaryKey(self::$data);
        self::setName(self::$data, $service);
        self::setForeignKeys(self::$data, self::saveChild(self::TABLE, self::$child, self::$data, $service));
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    /**
     * Сохранить дочерние таблицы сервиса
     *
     * @param int    $id ID сервиса
     * @param xml    $service Сервис
     * @param string $property Свойство для выборки второстепенных данных, по умолчанию - навзание таблицы
     * @return void
     */
    public static function childSave($id, xml $service, $property = self::TABLE)
    {
        self::setId(self::$data, $id);
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), self::extractByProperty($property, $service), self::$data);
    }

    /**
     * Добавить выборку для текущей таблицы
     *
     * @param string $for Для какой части сайта
     * @param array  $select Текущий набор выборки
     * @return void
     */
    public static function addSelect($for, array &$select)
    {
        $select = array_merge($select, self::getSelect($for));
    }

    /**
     * Получить выборку для текущей таблицы
     *
     * @param string $for Для какой части сайта
     * @param array  $select Текущая выборка
     * @return array Выборка
     * @throws \Exception
     */
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

    /**
     * Получить выборку для текущей дочерней таблицы
     *
     * @param string $for Для какой части сайта
     * @return array Выборка
     * @throws \Exception
     */
    public static function getChildSelect($for)
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        if (empty($fields))
        {
            throw new \Exception('not specified fields for selection in the table ' . self::TABLE);
        }
        return self::buildSelect(self::TABLE, $fields, [[self::ID, self::PARENT, self::getNameId(self::TABLE)]]);
    }

    /**
     * Получить текст статуса для текущего сервиса
     *
     * @param int $status Код текущего статуса
     * @return string Текст статуса
     * @throws \Exception
     */
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