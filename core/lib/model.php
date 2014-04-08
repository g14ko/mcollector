<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 1:54 PM
 */

namespace lib;

use lib\Sqlite as db;
use lib\Image as img;
use \SimpleXMLElement as xml;
use model\Collector as collector;
use model\collector\Server as server;
use model\collector\server\System as system;
use model\collector\server\Process as process;
use model\collector\server\FileSystem as filesystem;
use model\collector\server\Directory as directory;
use model\collector\server\File as file;
use model\collector\server\Host as host;

class Model
{
    const ID = 'id';
    const SERVERS = 'servers';
    const SERVER = 'server';
    const SERVICE = 'service';
    const SERVICES = 'services';
    const OPTIONS = 'options';
    const NAME = 'name';
    const COLLECTED = 'collected_sec';
    const ALIAS = 'alias';
    const STATUS = 'status';
    const STATUS_MESSAGE = 'status_message';
    const TYPE = 'type';
    const VERSION = 'version';
    const INCARNATION = 'incarnation';

    const UPDATE = 'update';

    const SELECT = 'select';
    const GROUP = 'group';
    const ORDER = 'order';
    const SAVE = 'save';
    const TABLES = 'tables';
    const FIELDS = 'fields';

    const MAX = 'max';
    const XPATH = 'xpath';

    // statuses of services
    const STATUS_RUNNING = 0;
    const STATUS_DOES_NOT_EXISTS = 512;
    const STATUS_FAILED = 4096;
    const STATUS_EXECUTION_FAILED = 4608;
    const START = 'start';
    const STOP = 'stop';
    const RESTART = 'restart';
    const NOT_MONITORED = 0;
    const MONITORED = 1;
    const INITIALIZATION = 2;

    const MONITOR = 'monitor';
    const UN_MONITOR = 'unmonitor';

    // types of services
    const FILE_SYSTEM = 0;
    const DIRECTORY = 1;
    const FILE = 2;
    const PROCESS = 3;
    const HOST = 4;
    const SYSTEM = 5;

    // M/Monit versions
    const V_5_1_1 = '5.1.1';
    const V_5_5 = '5.5';
    const V_5_5_1 = '5.5.1';
    const V_5_6 = '5.6';

    private static $xml;
    private static $alias;
    private static $version;
    private static $start;

    private static $services = [
        self::V_5_1_1 => [self::SERVICE],
        self::V_5_5   => [self::SERVICES, self::SERVICE],
        self::V_5_5_1 => [self::SERVICES, self::SERVICE],
        self::V_5_6   => [self::SERVICES, self::SERVICE]
    ];

    protected static $monitorImages = [
        self::MONITORED      => img::EYE,
        self::NOT_MONITORED  => img::EYE_CLOSE,
        self::INITIALIZATION => [
            'name'      => img::INITIALIZATION,
            'extension' => img::EXT_GIF
        ]
    ];

    protected static $monitorActions = [
        self::MONITORED     => self::UN_MONITOR,
        self::NOT_MONITORED => self::MONITOR
    ];

    protected static $tables = [
        self::SYSTEM      => system::TABLE,
        self::PROCESS     => process::TABLE,
        self::FILE_SYSTEM => filesystem::TABLE,
        self::FILE        => file::TABLE,
        self::DIRECTORY   => directory::TABLE,
        self::HOST        => host::TABLE
    ];

    protected static function getStartTimeStamp()
    {
        return self::$start;
    }

    protected static function setStartTimeStamp($time = null)
    {
        self::$start = !$time ? time() : $time;
    }

    protected static function replace($table, array $data)
    {
        return db::replace($table, $data);
    }

    protected static function saveServerStatus($server, $start, $end = null)
    {
        $data = [
            'server' => $server,
            'start'  => $start,
            'end'    => $end
        ];
        db::replace(self::SERVERS, $data);
    }

    protected static function cleanOldData($table, $field)
    {
        db::exec('DELETE FROM `' . $table . '` WHERE `' . $field . '` <= strftime(\'%s\',\'now\',\'-1 day\')');
    }

    protected static function cleanTable($table, array $pk)
    {
        db::delete($table, $pk);
    }

    protected static function config(array $path)
    {
        return config::get($path);
    }

    protected static function getFields($for, $table)
    {
        return config::get([$for, $table, self::FIELDS]);
    }

    protected static function getTableFields($table, $for)
    {
        return config::get([$table, $for, self::SELECT]);
    }

    protected static function getForeignKey()
    {
        return [self::SERVER => self::getAlias()];
    }

    protected static function saveToDB($table, array $fields, xml $xml, array $data = [])
    {
        foreach ($fields as $field)
        {
            $data[$field] = (string)$xml->{$field}; // todo: only strings ?
        }
        db::replace($table, $data);
    }

    protected static function setXml(xml $xml)
    {
        self::$xml = $xml;
    }

    protected static function extractXml($tag)
    {
        $xpath = config::get(['xpath', 'xml', $tag]);
        $xml = self::$xml->xpath($xpath);
        return !empty($xml) ? $xml[0] : null;
    }

    protected static function extractByXPath($name, xml $xml = null, array $result = [])
    {
        if ($xpath = self::config([self::XPATH, $name]))
        {
            !empty($xml) ? : $xml = self::$xml;
            $result = $xml->xpath($xpath);
        }
        return !empty($result) ? array_shift($result) : null;
    }

    protected static function xpath($xpath)
    {
        return self::$xml->xpath($xpath);
    }

    protected static function get($xpath)
    {
        $xml = self::$xml->xpath($xpath);
        return !empty($xml) ? (string)$xml[0] : null;
    }

    protected static function setIncarnationByVersion(array &$data)
    {
        $data[self::INCARNATION] = self::get(config::get([self::XPATH, self::INCARNATION, self::getVersion()]));
    }

    /**
     * @param array  $data Data for save to DB - set real version
     * @param string $version if isset - parse XML by this version
     */
    protected static function setVersion(array &$data, $version = null)
    {
        $xpath = config::get([self::XPATH, self::VERSION]);
        self::$version = !$version ? null : $version;
        $version = null;
        while (!empty($xpath) && !$version)
        {
            $version = self::get(array_pop($xpath));
        }
        self::$version ? : self::$version = $version;
        $data[self::VERSION] = $version;
    }

    protected static function getVersion()
    {
        return self::$version;
    }

    protected static function setAttributes(array &$data)
    {
        $attributes = self::extractAttributes();
        self::checkAttributes($attributes);
        foreach ($attributes as $attribute => $value)
        {
            $data[$attribute] = $value;
        }
    }

    private static function checkAttributes(array &$attributes)
    {
        foreach ([self::INCARNATION, self::VERSION] as $key)
        {
            if (!isset($attributes[$key]))
            {
                $attributes[$key] = (string)self::extract([self::SERVER, $key]);
            }
        }
    }

    private static function extractAttributes()
    {
        $attributes = [];
        $cut = [self::ID];
        foreach (self::$xml->attributes() as $attribute => $value)
        {
            if (!in_array($attribute, $cut))
            {
                $attributes[$attribute] = (string)$value;
            }
        }
        return $attributes;
    }

    /**
     * @param string $property
     * @param xml    $xml
     * @return xml
     */
    protected static function extractByProperty($property, xml $xml = null)
    {
        !empty($xml) ? : $xml = self::$xml;
        return $xml->{$property};
    }

    protected static function extract(array $properties = [])
    {
        if (!empty($properties))
        {
            $xml = self::$xml;
            foreach ($properties as $property)
            {
                $xml = $xml->$property;
            }
        }
        return $xml;
    }

    protected static function getServerServices(array $properties)
    {
        $xml = self::$xml;
        foreach ($properties as $property)
        {
            $xml = (is_object($xml) && !empty($property) && property_exists($xml, $property) && !empty($xml->{$property})) ?
                $xml->{$property} : [];
        }
        return $xml;
    }

    private static function getServiceKeyByVersion()
    {
        return self::$services[self::$version];
    }

    protected static function getServicesByVersion()
    {
        return self::getServerServices(self::getServiceKeyByVersion());
    }

    protected static function saveChild($parent, array $child, array $pk, xml $service, array $ids = [])
    {
        foreach ($child as $class => $table)
        {
            $fk = self::getNameId($table);
            $id = self::getByPk($parent, $fk, $pk);
            $class::save($id, $service);
            $ids[$fk] = !is_null($id) ? $id : self::lastInsertedId();
        }
        return $ids;
    }

    protected static function extractServicesByType($type)
    {
        $xpath = self::config([self::XPATH, self::SERVICES, self::getVersion()]);
        $xpath .= self::config([self::XPATH, self::TYPE, self::getVersion()]);
        $xpath = str_replace('{t}', $type, $xpath);
        return self::$xml->xpath($xpath);
    }

    protected static function setAlias($alias)
    {
        self::$alias = $alias;
    }

    protected static function getAlias()
    {
        return self::$alias;
    }

    protected static function lastInsertedId()
    {
        return (int)db::getLastInsertedId();
    }

    protected static function getByPk($table, $search, array $pk)
    {
        $select = self::buildSelect($table, [$search]);
        foreach ($pk as $field => $value)
        {
            $where[] = self::buildExpression($field, '=', $value, 'and');
        }
        unset($where[0]['binary']);
        return ($res = db::select($select, $where)) ? (int)array_shift($res)[$search] : null;
    }

    protected static function buildSelect($table, array $fields = [], array $join = [])
    {
        $select = [
            $table => [
                'fields' => !empty($fields) ? $fields : ['*']
            ]
        ];
        if (!empty($join))
        {
            foreach ($join as $on)
            {
                list($self, $onTable, $onField) = $on;
                $select[$table]['on'][] = [
                    'self'  => $self,
                    'table' => $onTable,
                    'field' => $onField
                ];
            }

        }
        return $select;
    }

    protected static function fetch(array $select, array $where = [], array $group = [], array $order = [])
    {
        $select = db::select($select, $where, $group, $order);
        $select = !empty($select) ? array_shift($select) : [];
        return !empty($select) ? array_shift($select) : $select;
    }

    protected static function select(array $select, array $where = [], array $group = [], array $order = [])
    {
        return db::select($select, $where, $group, $order);
    }


    protected static function cutSystemChildTableName($table)
    {
        return str_replace(system::TABLE, '', $table);
    }

    protected static function getSelectByTable($table, $for)
    {
        switch (true)
        {
            case $table == self::$tables[self::SYSTEM]:
                return system::getSelect($for);
            case $table == self::$tables[self::PROCESS]:
                return Process::getSelect($for);
            case $table == self::$tables[self::FILE_SYSTEM]:
                return FileSystem::getSelect($for);
            case $table == self::$tables[self::FILE]:
                return File::getSelect($for);
            case $table == self::$tables[self::DIRECTORY]:
                return Directory::getSelect($for);
            case $table == self::$tables[self::HOST]:
                return host::getSelect($for);
        }
    }

    protected static function addAliasForName($table, $name)
    {
        return $name . ' as ' . $table;
    }

    protected static function addAliases($table, array $fields)
    {
        foreach ($fields as &$field)
        {
            $field = $field . ' as ' . $table . $field;
        }
        return $fields;
    }

    protected static function needUpdateDb()
    {
        return db::needUpdate();
    }

    protected static function buildExpression($field, $operator, $value, $binary = '')
    {
        $expression = [
            'field'    => $field,
            'operator' => $operator,
            'value'    => $value
        ];
        if ($binary)
        {
            $expression['binary'] = $binary;
        }
        return $expression;
    }

    protected static function countServices($alias, $all = 0, $active = 0, $key = 'count', $sp = '/')
    {
        $updateTime = (int)collector::getUpdateTimeStamp($alias);
        $where = [
            self::buildExpression(self::SERVER, '=', $alias),
            self::buildExpression(self::UPDATE, '=', $updateTime, 'and')
        ];
        foreach (self::$tables as $table)
        {
            if (!in_array($table, [system::TABLE, host::TABLE]))
            {
                $all += db::getRowCount($table, $where);
                $active += db::getRowCount(
                             $table,
                             array_merge(
                                 $where,
                                 [self::buildExpression(self::STATUS, '=', (string)self::STATUS_RUNNING, 'and')],
                                 [self::buildExpression(self::MONITOR, '=', (string)self::MONITORED, 'and')]
                             )
                );
            }
        }
        return [$key => $all . $sp . $active];
    }

    protected static function setChildSelect($for, array $child, array &$select, array &$fields)
    {
        foreach ($child as $class => $table)
        {
            if (isset($fields[$table]))
            {
                $class::addSelect($for, $select);
                unset($fields[$table]);
            }
        }
    }

    public static function getUpdateTimeStamp($alias)
    {
        $update = self::select(self::buildSelect(self::SERVERS, ['start']), [self::buildExpression(self::SERVER, '=', $alias)]);
        empty($update) ? : $update = array_shift($update);
        return !empty($update) ? (int)array_shift($update) : (int)$update; // todo: refactoring this
    }

    protected static function setPrimaryKey(array &$data, $field = self::SERVER)
    {
        $data = self::getPrimaryKey($field);
    }

    protected static function getPrimaryKey($field)
    {
        return [
            $field       => self::getAlias(),
            self::UPDATE => self::getStartTimeStamp()
        ];
    }

    protected static function setName(array &$data, xml $service)
    {
        $data[self::NAME] = self::extractNameByVersion($service);
    }

    private static function extractNameByVersion(xml $xml)
    {
        return (string)$xml->xpath(self::config([self::XPATH, self::NAME, self::getVersion()]))[0]; //todo: handle errors
    }

    protected static function setId(array &$data, $id)
    {
        $data[self::ID] = $id;
    }

    protected static function setForeignKeys(array &$data, array $values)
    {
        if (!empty($values))
        {
            foreach ($values as $name => $value)
            {
                self::setForeignKey($data, $name, $value);
            }
        }
    }

    private static function setForeignKey(array &$data, $name, $value)
    {
        $data[$name] = $value;
    }

    protected static function getNameId($name)
    {
        return $name . ucfirst(self::ID);
    }

    protected static function stickTableNames(array $tables)
    {
        return implode('-', $tables);
    }

    protected static function extractTypeByVersion(xml $service)
    {
        switch (true)
        {
            case (self::getVersion() == self::V_5_1_1):
                return (int)$service->attributes()[self::TYPE];
            case (in_array(self::getVersion(), [self::V_5_5, self::V_5_5_1, self::V_5_6])):
                return (int)$service->{self::TYPE};
        }
    }

}