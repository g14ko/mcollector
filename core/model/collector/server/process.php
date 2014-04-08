<?php
/**
 * @author j3nya
 * @date 8/14/13
 * @time 5:08 PM
 */

namespace model\collector\server;

use lib\Model as model;
use model\collector\server\process\Memory as memory;
use model\collector\server\process\Cpu as cpu;
use \SimpleXMLElement as xml;

class Process extends model
{
    const TABLE = 'process';

    private static $data = [];

    private static $child = [
        'model\collector\server\process\Cpu'    => cpu::TABLE,
        'model\collector\server\process\Memory' => memory::TABLE
    ];

    private static $statuses = [
        self::STATUS_RUNNING          => 'running',
        self::STATUS_DOES_NOT_EXISTS  => 'does not exists',
        self::STATUS_FAILED           => 'failed',
        self::STATUS_EXECUTION_FAILED => 'execution failed'
    ];

    public static function saveAll(array $processes)
    {
        self::cleanOldData(self::TABLE, self::UPDATE);
        foreach ($processes as $process)
        {
            self::save($process);
        }
    }

    public static function save(xml $service)
    {

        self::setPrimaryKey(self::$data);
        self::setName(self::$data, $service);
        self::setForeignKeys(self::$data, self::saveChild(self::TABLE, self::$child, self::$data, $service));
        self::saveToDB(self::TABLE, self::getFields(self::SAVE, self::TABLE), $service, self::$data);
    }

    public static function getSelect($for, array $select = [])
    {
        $fields = self::config([$for, self::TABLE, self::SELECT]);
        self::setChildSelect($for, self::$child, $select, $fields);
        return array_merge(self::buildSelect(self::TABLE, $fields), $select);
    }

    public static function getStatus($status)
    {
        return self::$statuses[$status];
    }

}