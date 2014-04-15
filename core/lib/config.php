<?php
/**
 * @author j3nya
 * @date 7/30/13
 * @time 1:26 PM
 */
namespace lib;

class Config
{
    // directory and file extension for config ini files
    const INI_DIR = 'config';
    const INI_EXT = '.ini';
    // config names
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
    // file names for configs
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
    // current config
    private static $config;
    // sub directory for current config
    private static $subDirectory;
    // file path for current config
    private static $filepath;

    /**
     * Получить конфиг по заданному пути
     *
     * @param array $path Путь
     * @return mixed Конфиг
     */
    public static function get(array $path)
    {
        $config = strtolower(array_shift($path));
        return !self::init($config) ? null : self::extractData($config, $path);
    }

    /**
     * Инициализировать путь к конфигу и загрузить его
     *
     * @param string $name Название конфига
     * @return bool Признак успешной инициализации и загрузки конфига
     * <dl>
     *  <dt>true</dt><dd>конфиг загружен</dd>
     *  <dt>false</dt><dd>конфиг не загружен</dd>
     * </dl>
     */
    private static function init($name)
    {
        self::setSubDirectory($name);
        return self::setFileName($name) && self::load();
    }

    /**
     * Задать поддиректорию для конфига
     *
     * @param string $name Название конфига
     * @return void
     */
    private static function setSubDirectory(&$name)
    {
        if (strpos($name, '-') != false)
        {
            self::$subDirectory = explode('-', $name);
            $name = array_pop(self::$subDirectory);
            self::$subDirectory = implode(DIRECTORY_SEPARATOR, self::$subDirectory);
        }
        else
        {
            self::$subDirectory = null;
        }
    }

    /**
     * Задать путь к файлу для конфига
     *
     * @param string $name Название конфига
     * @return string Название файла, если файл не найден - null
     */
    private static function setFileName($name)
    {
        return !isset(self::$files[$name]) ? null :
            self::$filepath = self::getConfigDir() . self::getSubDirectory() . self::$files[$name] . self::INI_EXT;
    }

    /**
     * Возвращает поддиректорию для текущего конфига
     *
     * @return string Поддиректория текущего конфига
     */
    private static function getSubDirectory()
    {
        return !self::$subDirectory ? null : self::$subDirectory . DIRECTORY_SEPARATOR;
    }

    /**
     * Возвращает директорию с конфигами
     *
     * @return string Директория где хранятся конфиги
     */
    private static function getConfigDir()
    {
        return self::getCoreDir() . self::INI_DIR . DIRECTORY_SEPARATOR;
    }

    /**
     * Возвращает директорию ядра проекта
     *
     * @return string Директория ядра проекта
     */
    private static function getCoreDir()
    {
        return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;
    }

    /**
     * Загрузить конфиг
     *
     * @return bool Признак успешной загрузки конфига
     * <dl>
     *  <dt>true</dt><dd>конфиг загружен</dd>
     *  <dt>false</dt><dd>конфиг не загружен</dd>
     * </dl>
     */
    private static function load()
    {
        return self::isFileExists() && (bool)self::$config = self::parse(self::$filepath);
    }

    /**
     * Проверка на существования файла с конфигом
     *
     * @return bool Провекра файла с конфигом
     * <dl>
     *  <dt>true</dt><dd>файла существует</dd>
     *  <dt>false</dt><dd>файла не существует</dd>
     * </dl>
     */
    private static function isFileExists()
    {
        return \file_exists(self::$filepath);
    }

    /**
     * Разобрать файл с конфигом
     *
     * @return array Данные конфига
     */
    private static function parse()
    {
        return \parse_ini_file(self::$filepath, true);
    }

    /**
     * Извлечь нужную часть конфига
     *
     * @param string $section Название конфига
     * @param array  $sections Путь к нужной части конфига
     * @return mixed Часть необходимого конфига, если конфиг или искомая секция не найдена - null
     */
    private static function extractData($section, array $sections)
    {
        self::setSectionAsConfig($section, false);
        if (!empty($sections))
        {
            foreach ($sections as $section)
            {
                self::setSectionAsConfig($section);
            }
        }
        return self::getConfig();
    }

    /**
     * Задать секцию, как конфиг
     *
     * @param string $section Название секции
     * @param bool   $clear Очистить конфиг, если секция не найдена
     * <dl>
     *  <dt>true</dt><dd>да, очистить конфиг</dd>
     *  <dt>false</dt><dd>нет, оставить предыдущую секцию, как конфиг</dd>
     * </dl>
     * @return void
     */
    private static function setSectionAsConfig($section, $clear = true)
    {
        self::$config = !isset(self::$config[$section]) ? !$clear ? self::$config : [] : self::$config[$section];
    }

    /**
     * Возвращает текущий конфиг
     *
     * @return mixed текущий конфиг, если текущий конфиг пустой - null
     */
    private static function getConfig()
    {
        return !empty(self::$config) ? self::$config : null;
    }

    /**
     * Задана ли секция в конфиге
     *
     * @param array  $config Путь к конфигу
     * @param string $section Название секции
     * @return bool Задана ли секция в конфиге
     * <dl>
     *  <dt>true</dt><dd>да</dd>
     *  <dt>false</dt><dd>нет</dd>
     * </dl>
     */
    public static function issetSection(array $config, $section)
    {
        return isset(self::get($config)[$section]);
    }

}