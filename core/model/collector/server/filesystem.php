<?php
/**
 * @author j3nya
 * @date 8/15/13
 * @time 4:16 PM
 */

namespace model\collector\server;

use lib\Model as model;
use model\collector\server\filesystem\Block as block;
use model\collector\server\filesystem\Inode as inode;

class FileSystem extends model
{
    use \component\Model;

    const TABLE = 'filesystem';

    private static $data = [];

    private static $child = [
        'model\collector\server\filesystem\Block' => block::TABLE,
        'model\collector\server\filesystem\Inode' => inode::TABLE
    ];

    private static $statuses = [
        self::STATUS_RUNNING => 'accessible',
        self::STATUS_DOES_NOT_EXISTS => 'not accessible'
    ];

}