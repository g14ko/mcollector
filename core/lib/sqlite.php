<?php
/**
 * @author j3nya
 * @date 8/12/13
 * @time 11:49 AM
 */

namespace lib;

use lib\AutoLoader as loader;

class Sqlite
{
    private static $directory = 'db';
    private static $file = 'monit.sqlite';

    private static $db = null;
    private static $stmt = null;

    public static function __callStatic($method, array $params = [])
    {
        if (!self::$db)
        {
            self::openConnection();
        }
        return forward_static_call_array([__CLASS__, $method], $params);
    }

    private static function init()
    {
        foreach (config::get(['db']) as $table => $fields)
        {
            self::dropTable($table);
            self::createTable($table, array_values($fields));
        }
        return true;
    }

    private static function getAbsoluteFilePath()
    {
        return loader::getRootDirectory() . self::$directory . '/' . self::$file;
    }

    private static function openConnection()
    {
        $file = self::getAbsoluteFilePath();
        self::$db = new \PDO('sqlite:' . $file);
        self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    private static function createTable($name, array $fields = [])
    {
        self::exec('CREATE TABLE ' . $name . ' (' . implode(', ', $fields) . ')');
    }

    private static function delete($name, array $where)
    {
        $expression = [];
        $sql = 'DELETE FROM ' . $name;
        foreach (array_keys($where) as $field)
        {
            $expression[] = self::wrappedInQuotes($field) . '=' . self::keyAlias($field);
        }
        $sql .= ' WHERE ' . implode(' AND ', $expression);
        self::$stmt = self::$db->prepare($sql);
        foreach ($where as $key => $value)
        {
            self::$stmt->bindValue(self::keyAlias($key), $value, self::getType($value));
        }
        self::execute();
    }

    private static function replace($name, array $values = [])
    {
        $sql = 'REPLACE INTO ' . $name . ' ';
        $sql .= '(' . implode(', ', array_map([__CLASS__, 'wrappedInQuotes'], array_keys($values))) . ') ';
        $sql .= 'VALUES (' . implode(', ', self::keyAliases(array_keys($values))) . ')';
        self::$stmt = self::$db->prepare($sql);
        foreach ($values as $key => $value)
        {
            self::$stmt->bindValue(self::keyAlias($key), $value, self::getType($value));
        }
        self::execute();
    }

    private static function wrappedInQuotes($field)
    {
        return '`' . $field . '`';
    }

    private static function select(array $select = [], array $where = [], array $group = [], array $order = [])
    {
        $sql = self::buildSelection($select);
        if (!empty($where))
        {
            foreach ($where as $expression)
            {
                list($sqlWhere, $alias, $value) = self::buildExpression($expression, empty($values));
                $sql .= $sqlWhere;
                $values[$alias] = $value;
            }
            self::$stmt = self::$db->prepare($sql);
            foreach ($values as $key => $value)
            {
                self::$stmt->bindValue($key, $value, self::getType($value));
            }
            self::execute();
        }
        else
        {
            self::buildGroup($sql, $group);
            self::buildOrder($sql, $order);
            self::query($sql);
        }
        return self::fetchAll();
    }

    private static function buildGroup(&$sql, array $group = [])
    {
        if (!empty($group))
        {
            $sql .= ' GROUP BY ';
            foreach ($group as $table => $fields)
            {
                $table = self::wrappedInQuotes($table);
                $fields = array_map([__CLASS__, 'wrappedInQuotes'], array_keys($fields));
                $sql .= $table . '.' . implode(', ' . $table . '.', $fields);
            }
        }
    }

    private static function buildOrder(&$sql, array $order = [])
    {
        if (!empty($order))
        {
            $sql .= ' ORDER BY ';
            foreach ($order as $table => $fields)
            {
                $table = self::wrappedInQuotes($table);
                $fields = array_map([__CLASS__, 'wrappedInQuotes'], array_keys($fields));
                $sql .= $table . '.' . implode(', ' . $table . '.', $fields);
            }
        }
    }

    protected static function getRowCount($table, array $where)
    {
        $sql = 'SELECT count(*) as count FROM ' . $table;
        foreach ($where as $expression)
        {
            list($sqlWhere, $alias, $value) = self::buildExpression($expression, empty($values));
            $sql .= $sqlWhere;
            $values[$alias] = $value;
        }
        self::$stmt = self::$db->prepare($sql);
        foreach ($values as $key => $value)
        {
            self::$stmt->bindValue($key, $value, self::getType($value));
        }
        self::execute();
        return (int)self::extractResult(self::fetchAll(), 2);
    }

    public static function extractResult(array $result, $depth = 1)
    {
        while ($depth > 0 && !empty($result))
        {
            $result = array_shift($result);
            $depth--;
        }
        return $result;
    }

    private static function selectAll($table)
    {
        $sql = 'SELECT * FROM ' . self::wrappedInQuotes($table);
        self::query($sql);
        return self::fetchAll();
    }

    private static function buildSelection(array $select = [])
    {
        $tables = [];
        $fields = [];
        $joins = [];
        foreach ($select as $table => $params)
        {
            $table = self::wrappedInQuotes($table);
            foreach ($params['fields'] as $field => $sql)
            {
                $field = self::wrappedInQuotes($field);
                $fields[] = !empty($sql) ? str_replace(['{t}', '{f}'], [$table, $field], $sql) : $table . '.' . $field;
            }
            if (!empty($tables))
            {
                foreach ($params['on'] as $on)
                {
                    $joins[$table][] = $table . '.' . self::wrappedInQuotes($on['self']) . '=' . self::wrappedInQuotes($on['table']) . '.' . self::wrappedInQuotes($on['field']);
                }
            }
            $tables[] = $table;
        }
        $sql = 'SELECT ';
        $sql .= implode(', ', $fields);
        $sql .= ' FROM ' . array_shift($tables);
        if (!empty($tables))
        {
            foreach ($tables as $table)
            {
                $sql .= ' LEFT JOIN ' . $table . ' ON ' . implode(' AND ', $joins[$table]);
            }
        }
        return $sql;
    }

    private static function buildExpression(array $expression = [], $mainExpression = false)
    {
        $alias = self::keyAlias($expression['field']);
        $where = ($mainExpression) ?
            ' WHERE ' :
            ' ' . $expression['binary'] . ' ';
        $where .= '`' . $expression['field'] . '` ' . $expression['operator'] . ' ' . $alias;
        return [$where, $alias, $expression['value']];
    }

    private static function dropTable($name)
    {
        $sql = 'DROP TABLE IF EXISTS ' . self::wrappedInQuotes($name);
        self::exec($sql);
    }

    private static function closeConnection()
    {
        self::$db = null;
    }

    private static function keyAlias($key)
    {
        return ':' . $key;
    }

    private static function keyAliases(array $keys = [])
    {
        foreach ($keys as $i => $key)
        {
            $keys[$i] = self::keyAlias($key);
        }
        return $keys;
    }

    private static function getType($value)
    {
        if (is_int($value))
        {
            return SQLITE3_INTEGER;
        }
        elseif (is_string($value))
        {
            return SQLITE3_TEXT;
        }
        elseif (is_null($value))
        {
            return SQLITE3_NULL;
        }
        elseif (is_float($value))
        {
            return SQLITE3_FLOAT;
        }
    }

    private static function needUpdate()
    {
        return (time() > (config::get(['refresh', 'interval']) + self::lastModifiedDb()));
    }

    private static function lastModifiedDb()
    {
        $file = self::getAbsoluteFilePath();
        if (!file_exists($file))
        {
            throw new \Exception('File not found');
        }
        return filemtime($file);
    }

    private static function getTables()
    {
        self::query('SELECT `name` FROM `sqlite_master` WHERE type = \'table\' ORDER BY `name`');
        return self::fetchAll();
    }

    private static function getStructureOfTable($name)
    {
        self::query('PRAGMA table_info(' . self::wrappedInQuotes($name) . ')');
        return self::fetchAll();
    }

    private static function execute()
    {
        try
        {
            self::$stmt->execute();
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    private static function exec($sql)
    {
        try
        {
            self::$db->exec($sql);
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    private static function fetchAll()
    {
        return self::$stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private static function query($sql)
    {
        self::$stmt = self::$db->query($sql);
    }

    private static function getLastInsertedId()
    {
        return self::$db->lastInsertId();
    }

    private static function isEmpty()
    {
        return (bool)!self::getTables();
    }

}