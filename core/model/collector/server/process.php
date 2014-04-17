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

class Process extends model
{
    use \component\Model;

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

}