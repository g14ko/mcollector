<?php
/**
 * @author eugene
 * @date 2/10/14
 * @time 3:10 PM
 */

namespace model;

use lib\Model as model;
use model\collector\Server as server;
use model\collector\server\System as system;

class Collector extends model
{
    use \component\Model;

    const TABLE = 'collector';

    const START = 'start';
    const END = 'end';

    private static $data = [];

    private static $child = [
        'model\collector\server\System' => system::TABLE,
        'model\collector\Server' => server::TABLE
    ];

    public static function saveServer($server, $start)
    {
        self::setData($server, $start);
        self::replace(self::TABLE, self::$data);
    }

    public static function getServers($for)
    {
        return self::select(self::getSelect($for), [], [], self::getOrder($for));
    }

    public static function getOrder($for)
    {
        return [self::TABLE => self::config([$for, self::TABLE, self::ORDER])];
    }

    public static function addUpdateTime(array $select, array $on)
    {
        array_unshift($on, self::ALIAS);
        return array_merge(
            $select,
            self::buildSelect(self::TABLE, [self::END => '{t}.{f} AS updated'], [$on])
        );
    }

    public static function getUpdateTimeStamp($alias)
    {
        return self::fetch(self::buildSelect(self::TABLE, [self::START]), [self::buildExpression(self::ALIAS, '=', $alias)]);
    }

    private static function setData($server, $start)
    {
        self::$data = [
            self::ALIAS => $server,
            self::START  => $start,
            self::END    => time()
        ];
    }
} 