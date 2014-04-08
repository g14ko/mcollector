<?php
/**
 * @author j3nya
 * @date 10/3/13
 * @time 11:43 AM
 */

namespace lib;


class Log
{
    private static $file = null;

    public static function create($file = null, $data = null)
    {
        if (!self::setFilePath($file))
        {
            return false;
        }
        self::write($data, true);
    }

    public static function add($file = null, $data = null)
    {
        if (!self::setFilePath($file))
        {
            return false;
        }
        self::write($data);
    }

    private static function setFilePath($filename = null)
    {
        self::unsetFileName();
        if (!empty($filename))
        {
            self::$file = realpath(AutoLoader::getRootDirectory() . DIRECTORY_SEPARATOR . '..') .
                DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $filename . '.log';
        }
        return self::issetFileName();
    }

    private static function issetFileName()
    {
        return !empty(self::$file);
    }

    private static function unsetFileName()
    {
        self::$file = null;
    }

    private static function write($data, $append = false)
    {
        if (!empty($data) && !empty(self::$file))
        {
            (!$append) ?
                file_put_contents(self::$file, $data . "\n", FILE_APPEND | LOCK_EX) :
                file_put_contents(self::$file, $data . "\n", LOCK_EX);
        }
    }

}