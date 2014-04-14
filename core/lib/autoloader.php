<?php
/**
 * User: j3nya
 * Date: 10.08.13
 * Time: 19:37
 */

namespace lib;

class AutoLoader
{
    const ROOT = 0;
    const PATH = 1;
    const NAME = 2;
    const EXT = 3;
    const AUTO_LOAD = 'load';


    private static $colon = ':';
    private static $slash = '/';
    private static $backSlash = '\\';
    private static $rootDir = '';
    private static $phpExt = '.php';
    private static $timeZone = 'Europe/Kiev';

    private static $file = [];

    public static function getRootDirectory()
    {
        return self::$rootDir;
    }

    public static function load($fileNameSpace)
    {
        self::initPath();
        self::parseNameSpace($fileNameSpace);
        $file = self::getFile();
        if (self::fileExists($file))
        {
            require_once($file);
        }
    }

    public static function initPath()
    {
        self::$file = [
            self::ROOT => self::$rootDir,
            self::EXT  => self::$phpExt
        ];
    }

    public static function init($dir)
    {
        self::debugMode();
        self::setTimeZone();
        self::$rootDir = realpath($dir . DIRECTORY_SEPARATOR . '../core') . DIRECTORY_SEPARATOR;
        self::register();
    }

    private static function setTimeZone()
    {
        \date_default_timezone_set(self::$timeZone);
    }

    private static function parseNameSpace($nameSpace)
    {
        if (strpos($nameSpace, self::$backSlash) !== false)
        {
            $parsedNameSpace = explode(self::$backSlash, $nameSpace);
            self::$file[self::NAME] = strtolower(array_pop($parsedNameSpace));
            self::$file[self::PATH] = implode(self::$slash, $parsedNameSpace) . self::$slash;
        }
        else
        {
            self::$file[self::NAME] = strtolower($nameSpace);
        }
    }

    private static function fileExists($file)
    {
        if (file_exists($file))
        {
            self::$file = $file;
            return true;
        }
        return false;
    }

    private static function getFile()
    {
        ksort(self::$file);
        return implode('', self::$file);
    }

    private static function getPathToFile($fileName)
    {
        if (strpos($fileName, self::$backSlash) !== false)
        {
            $fileName = implode(self::$slash, explode(self::$backSlash, $fileName));
        }
        return strtolower($fileName);
    }

    private static function doubleColon()
    {
        return self::$colon . self::$colon;
    }

    private static function register()
    {
        \spl_autoload_register(__CLASS__ . self::doubleColon() . self::AUTO_LOAD);
    }

    private static function debugMode()
    {
        if (DEBUG_MODE)
        {
            ini_set('display_errors', true);
            ini_set('display_startup_errors', true);
            error_reporting(E_ALL);
        }
    }

}