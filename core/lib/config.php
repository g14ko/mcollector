<?php
/**
 * @author j3nya
 * @date 7/30/13
 * @time 1:26 PM
 */
namespace lib;

class Config
{
    const INI_DIR = 'config';
    const INI_EXT = '.ini';

    const SERVERS = 'servers';
    const LAYOUT = 'layout';
    const PAGE = 'page';
    const DB = 'db';
    const ACTIONS = 'actions';
    const OPTIONS = 'options';
    const FIELDS = 'fields';
    const SELECT = 'select';
    const SERVICES = 'services';
    const SAVE = 'save';
    const CONTENT = 'content';
    const ASIDE = 'aside';
    const PARAMETERS = 'parameters';
    const XPATH = 'xpath';

    private static $files = [
        'servers'    => self::SERVERS,
        'styles'     => self::LAYOUT,
        'scripts'    => self::LAYOUT,
        'head'       => self::PAGE,
        'refresh'    => self::PAGE,
        'db'         => self::DB,
        'actions'    => self::ACTIONS,
        'select'     => self::SELECT,
        'services'   => self::SERVICES,
        'options'    => self::OPTIONS,
        'save'       => self::SAVE,
        'content'    => self::CONTENT,
        'aside'      => self::ASIDE,
        'parameters' => self::PARAMETERS,
        'xpath'      => self::XPATH,
    ];

    private static $directory;
    private static $file;
    private static $config;

    public static function get(array $path)
    {
        $config = strtolower(array_shift($path));
        return !self::init($config) ? null : self::extractData($config, $path);
    }

    public static function issetSection(array $config, $section)
    {
        return isset(self::get($config)[$section]);
    }

    private static function extractData($section, array $sections)
    {
        self::setConfig($section, false);
        if (!empty($sections))
        {
            foreach ($sections as $section)
            {
                self::setConfig($section);
            }
        }
        return self::getConfig();
    }

    private static function getConfig()
    {
        return !empty(self::$config) ? self::$config : null;
    }

    private static function setConfig($key, $clear = true)
    {
        self::$config = !isset(self::$config[$key]) ? !$clear ? self::$config : [] : self::$config[$key];
    }

    private static function init($name)
    {

        return self::setSubDirectory($name) && self::setFileName($name) && self::setFilePath() && self::load();
    }

    private static function load()
    {
        return self::isFileExists() && self::$config = self::parse(self::$file);
    }

    private static function getSubDirectory()
    {
        return !self::$directory ? null : self::$directory . DIRECTORY_SEPARATOR;
    }

    private static function setSubDirectory(&$name)
    {
        if (strpos($name, '-') != false)
        {
            self::$directory = explode('-', $name);
            $name = array_pop(self::$directory);
            self::$directory = implode(DIRECTORY_SEPARATOR, self::$directory);
        }
        else {
            self::$directory = null;
        }
        return true;
    }

    private static function setFileName($name)
    {
        if (!array_key_exists($name, self::$files))
        {
            return false;
        }
        return self::$file = self::$files[$name];
    }

    private static function setFilePath()
    {
        return self::$file = self::getConfigDir() . self::getSubDirectory() . self::$file . self::INI_EXT;
    }

    private static function isFileExists()
    {
        return file_exists(self::$file);
    }

    private static function parse()
    {
        return parse_ini_file(self::$file, true);
    }

    private static function getConfigDir()
    {
        return self::getRootDir() . self::INI_DIR . DIRECTORY_SEPARATOR;
    }

    private static function getRootDir()
    {
        return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;
    }

}