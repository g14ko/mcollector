<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 1:37 PM
 */

namespace model\collector;

use lib\Model as model;
use model\Collector as collector;
use model\collector\server\HttpD as httpd;
use model\collector\server\Process as process;
use model\collector\server\System as system;
use model\collector\server\FileSystem as filesystem;
use model\collector\server\Directory as directory;
use model\collector\server\File as file;
use model\collector\server\Host as host;
use \SimpleXMLElement as xml;

class Server extends model
{
    const TABLE = self::SERVER;

    private static $data = [];

    private static $services = [
        'model\collector\server\Host'       => self::HOST,
        'model\collector\server\System'     => self::SYSTEM,
        'model\collector\server\Process'    => self::PROCESS,
        'model\collector\server\FileSystem' => self::FILE_SYSTEM,
        'model\collector\server\Directory'  => self::DIRECTORY,
        'model\collector\server\File'       => self::FILE
    ];

    /**
     * @param string $alias Server alias
     * @param xml    $xml XML document
     * @param string $version Parse XML by this version
     */
    public static function save($alias, xml $xml, $version = null)
    {
        // init mandatory properties
        self::setStartTimeStamp();
        self::setXml($xml);
        self::setAlias($alias);
        // set PK and attributes(version, incarnation)
        self::setPrimaryKey(self::$data, self::ALIAS);
        self::setVersion(self::$data, $version);
        self::setIncarnationByVersion(self::$data);
        // if isset event - save only event
        if ($event = self::extractByProperty(event::TABLE))
        {
            self::setStartTimeStamp((int)collector::getUpdateTimeStamp($alias));
            event::save($event);
        }
        else
        {
            $server = self::extractByXPath(self::TABLE);
            self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $server, self::$data);
            httpd::save($server);
            platform::save();
            self::saveServices();
            collector::saveServer($alias, self::getStartTimeStamp());
        }
    }

    public static function getTimeCollected($server, array $tables, $service = null)
    {
        $services = [];
        $fields = [self::NAME, self::COLLECTED . ' as collected'];
        $where = [
            self::buildExpression(self::SERVER, '=', $server),
            self::buildExpression(self::UPDATE, '=', self::getStartTimeStamp(), 'and')
        ];
        if(!empty($service))
        {
            array_push($where, self::buildExpression(self::NAME, '=', $service, 'and'));
        }
        foreach ($tables as $table)
        {
            if ($select = self::select(self::buildSelect($table, $fields), $where))
            {
                !self::isSystemTable($table) ? : $select[0][self::NAME] = $server;
                $services = array_merge($services, $select);
            }
        }
        return $services;
    }

    public static function getServers($for)
    {
        $for .= '-' . self::SERVERS;
        return array_map([__CLASS__, 'addCountServices'], collector::getServers($for));
    }

    public static function addSelect($for, array &$select)
    {
        $select = array_merge($select, self::getSelect($for));
    }

    public static function getSelect($for, array $select = [])
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        return array_merge(self::buildSelect(
                               self::TABLE,
                               $fields,
                               [
                                   [self::ALIAS, collector::TABLE, self::ALIAS],
                                   [self::UPDATE, collector::TABLE, collector::START]
                               ]), $select);
    }

    private static function addCountServices(array $server)
    {
        $alias = array_shift($server);
        return array_merge(
            [self::SERVER => $alias],
            self::countServices($alias),
            $server
        );
    }

    public static function getPollTime($alias)
    {
        $updateTime = (int)collector::getUpdateTimeStamp($alias);
        return self::select(self::buildSelect(self::TABLE, ['poll']), [
            self::buildExpression(self::ALIAS, '=', $alias),
            self::buildExpression(self::UPDATE, '=', $updateTime, 'and')
        ])[0]['poll'];
    }

    public static function getServices($alias, $for, array $services = [])
    {
        $for .= '-' . self::SERVICES;
        $updateTime = (int)collector::getUpdateTimeStamp($alias);
        $where = [
            self::buildExpression(self::SERVER, '=', $alias),
            self::buildExpression(self::UPDATE, '=', $updateTime, 'and')
        ];
        foreach (self::config([$for, self::TABLES]) as $table => $class)
        {
            $select = !self::classExists($class) ? self::getSelectByTable($table, $for) : $class::getSelect($for);
            $services[$table] = self::select($select, $where);
        }
        return $services;
    }

    private static function classExists($class)
    {
        return !empty($class) && class_exists($class);
    }

    public static function getOptions($alias, $type, $name, $for)
    {
        $for .= '-' . self::OPTIONS;
        $updateTime = (int)collector::getUpdateTimeStamp($alias);
        $options = self::select(
                       self::getSelectByTable($type, $for),
                       [
                           self::buildExpression(self::SERVER, '=', $alias),
                           self::buildExpression(self::NAME, '=', $name, 'and'),
                           self::buildExpression(self::UPDATE, '=', $updateTime, 'and')
                       ]
        );
        return !empty($options) ? self::turnArray(array_shift($options)) : $options;
    }

    public static function isProcessType($type)
    {
        return $type === process::TABLE;
    }

    private static function isSystemTable($table)
    {
        return $table === system::TABLE;
    }

    public static function getEvents($alias)
    {
        $update = (int)collector::getUpdateTimeStamp($alias);
        $events = self::select(
                      event::getSelect(),
                      [
                          self::buildExpression(self::SERVER, '=', $alias),
                          self::buildExpression(self::UPDATE, '=', $update, 'and')
                      ]
        );
        return !empty($events) ? $events[0] : $events;
    }

    public static function getClassByStatus($status)
    {
        switch (true)
        {
            case in_array($status, ['running', 'accessible', 'online with all services']):
                return 'green';
            case in_array($status, ['failed', 'does not exists']):
                return 'red';
        }
    }

    public static function getStatusByType($type, $status)
    {
        switch (true)
        {
            case ($type == process::TABLE):
                return process::getStatus($status);
            case ($type == filesystem::TABLE):
                return filesystem::getStatus($status);
            case ($type == file::TABLE):
                return file::getStatus($status);
            case ($type == directory::TABLE):
                return directory::getStatus($status);
            case ($type == host::TABLE):
                return host::getStatus($status);
        }
    }

    public static function getMonitorAction($value)
    {
        return isset(self::$monitorActions[$value]) ? self::$monitorActions[$value] : null;
    }

    public static function getMonitorImage($value)
    {
        return isset(self::$monitorImages[$value]) ? self::$monitorImages[$value] : null;
    }

    private static function turnArray(array $fields)
    {
        $parameters = [];
        foreach ($fields as $parameter => $value)
        {
            array_push($parameters, ['parameter' => $parameter, 'value' => $value]);
        }
        return $parameters;
    }

    private static function saveServices()
    {
        foreach (self::$services as $class => $type)
        {
            if ($services = self::extractServicesByType($type))
            {
                $class::saveAll($services);
            }
        }
    }

}