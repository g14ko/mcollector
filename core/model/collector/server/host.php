<?php
/**
 * @author eugene
 * @date 1/15/14
 * @time 12:36 PM
 */

namespace model\collector\server;

use lib\Model as model;
use model\collector\server\host\Port as port;

class Host extends model
{
    use \component\Model;

    const TABLE = 'host';

    private static $data = [];

    private static $child = [
        'model\collector\server\host\Port' => port::TABLE
    ];

    private static $statuses = [
        self::STATUS_RUNNING => 'online with all services', // todo statuses
        self::STATUS_DOES_NOT_EXISTS => 'not monitored',
        32 => 'unknown'
    ];

}