<?php
/**
 * @author j3nya
 * @date 7/31/13
 * @time 11:14 AM
 */

namespace lib;

use \SimpleXMLElement as xml;

class Response
{
    private static $xml;

    public static function save($string)
    {
        self::clean();
        try
        {
            self::$xml = new xml($string);
            self::saveToFile($string);
        }
        catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }

    private static function saveToFile($xml, $append = false)
    {
        if (!empty($xml))
        {
            $file = self::getFilePath();
            $xml = self::formatXml($xml);
            if (!empty($file['name']))
            {
                $file = implode('', $file);
                (!$append) ? file_put_contents($file, $xml, LOCK_EX) : file_put_contents($file, $xml, FILE_APPEND | LOCK_EX);
            }
        }
    }

    private static function formatXml($xml)
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        return $dom->saveXML();
    }

    private static function getFilePath()
    {
        return [
            'path' => 'xml' . DIRECTORY_SEPARATOR,
            'name' => self::getAlias(),
            'ext'  => '.xml'
        ];
    }

    public static function get()
    {
        return self::$xml;
    }

    public static function is()
    {
        return !empty(self::$xml);
    }

    public static function getAlias()
    {
        $alias = (string)self::$xml->server->localhostname;
        $alias = (!strpos($alias, '.')) ? [$alias] : explode('.', $alias);
        return array_shift($alias);
    }

    private static function clean()
    {
        self::$xml = null;
    }

}