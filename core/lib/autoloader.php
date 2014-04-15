<?php
/**
 * User: j3nya
 * Date: 10.08.13
 * Time: 19:37
 */

namespace lib;

class AutoLoader
{
    // name the method for register auto load
    const AUTO_LOAD = 'load';
    // indexes part of file path
    const CORE = 0;
    const PATH = 1;
    const NAME = 2;
    const EXT = 3;
    // default time zone
    const TIMEZONE = 'Europe/Kiev';
    // default time zone
    const CORE_DIR = 'core';
    // default file extension
    const PHP_EXT = '.php';
    // core directory
    private static $coreDir = null;
    // file path
    private static $filepath = [];

    /**
     * Инициализация авто загрузки
     *
     * @param $dir Публичная директория с индексным файлом
     * @return void
     */
    public static function init($dir)
    {
        self::setDebugMode();
        self::setTimeZone();
        self::setCoreDirectory($dir);
        self::register();
    }

    /**
     * Установить уровень вывода ошибок
     *
     * @return void
     */
    private static function setDebugMode()
    {
        if (DEBUG)
        {
            ini_set('display_errors', true);
            ini_set('display_startup_errors', true);
            error_reporting(E_ALL);
        }
    }

    /**
     * Задать часовой пояс
     *
     * @param string $timezone Название часового пояса
     * @return void
     */
    private static function setTimeZone($timezone = self::TIMEZONE)
    {
        \date_default_timezone_set($timezone);
    }

    /**
     * Задать директорию с ядром проекта
     *
     * @param string $dir Путь к публичной директории
     * @param string $coreDir Название директории с ядром проекта
     * @return void
     */
    private static function setCoreDirectory($dir, $coreDir = self::CORE_DIR)
    {
        self::$coreDir = realpath($dir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $coreDir) . DIRECTORY_SEPARATOR;
    }

    /**
     * Получить директорию с ядром проекта
     *
     * @return string Директория с ядром проекта
     * @return void
     */
    public static function getCoreDirectory()
    {
        return self::$coreDir;
    }

    /**
     * Зарегистрировать метод, реализующий функцию авто загрузки
     *
     * @return void
     */
    private static function register()
    {
        \spl_autoload_register(__CLASS__ . '::' . self::AUTO_LOAD);
    }

    /**
     * Метод для авто загрузки
     *
     * @param string $fileNameSpace Пространство имен файла
     * @return void
     */
    public static function load($fileNameSpace)
    {
        self::initFilePath();
        self::parseNameSpace($fileNameSpace);
        $file = self::getFilePath();
        self::fileExists($file) && require_once($file);
    }

    /**
     * Инициализировать путь к файлу
     *
     * @param string $extension Расширение файла, по умолчанию - .php
     * @return void
     */
    private static function initFilePath($extension = self::PHP_EXT)
    {
        self::$filepath = [
            self::CORE => self::getCoreDirectory(),
            self::EXT  => $extension
        ];
    }

    /**
     * Разобрать пространство имен и задать путь к файлу
     *
     * @param string $nameSpace Пространство имен
     * @return void
     */
    private static function parseNameSpace($nameSpace)
    {
        if (strpos($nameSpace, '\\') !== false)
        {
            $parsedNameSpace = explode('\\', $nameSpace);
            self::$filepath[self::NAME] = strtolower(array_pop($parsedNameSpace));
            self::$filepath[self::PATH] = implode(DIRECTORY_SEPARATOR, $parsedNameSpace) . DIRECTORY_SEPARATOR;
        }
        else
        {
            self::$filepath[self::NAME] = strtolower($nameSpace);
        }
    }

    /**
     * Собрать и вернуть путь к файлу
     *
     * @return string Путь к файлу
     * @return void
     */
    private static function getFilePath()
    {
        ksort(self::$filepath);
        return implode('', self::$filepath);
    }

    /**
     * Сущевствует ли файл по указаному пути
     *
     * @param string $file Путь к файлу
     * @return bool Файл сущевствует, true - да, false - нет
     */
    private static function fileExists($file)
    {
        return file_exists($file);
    }

}