<?php
/**
 * @author j3nya
 * @date 7/31/13
 * @time 2:59 PM
 */

namespace lib;

class View
{
    const VIEW_DIR = 'view';
    const LAYOUT = 'layout';
    const TPL_EXT = '.tpl';

    const ACTIONS = 'actions';
    const LINK_ACTION = 'action';
    const LINK_UPDATE = 'update';
    const LINK_ACTIVE = 'active';
    const LINK_INACTIVE = 'inactive';
    const ATTR_ID = 'id';
    const ATTR_CLASS = 'class';
    const ATTR_TITLE = 'title';

    public static function getServiceAttributes($service)
    {
        return [self::ATTR_ID => $service];
    }

    public static function getActionAttributes($alias, $service, $action, $existsUrl)
    {
        return [
            self::ATTR_CLASS => self::getActionClass($action, $existsUrl),
            self::ATTR_TITLE => self::getActionTittle($alias, $service, $action, $existsUrl)
        ];
    }

    private static function getActionTittle($alias, $service, $action, $existsUrl)
    {
        $words = !$existsUrl ?
            ['no', 'isset', 'URL', 'options', 'in', 'config', 'for', $alias] :
            [$action, $service, 'on', $alias];
        return implode(' ', $words);
    }

    private static function getActionClass($action, $existsUrl, array $classes = [])
    {
        return implode(' ', [$action, self::LINK_ACTION, !$existsUrl ? self::LINK_INACTIVE : self::LINK_ACTIVE]);
    }

    public static function th($data, array $attributes = [])
    {
        return self::createTag('th', $data, $attributes);
    }

    public static function tr(array $values, $header = false, array $attributes = [])
    {
        $tagName = !$header ? 'td' : 'th';
        foreach ($values as $class => &$value)
        {
            !$header ? : $class = $value;
            $value = self::createTag($tagName, self::span($value), ['class' => $class]);
        }
        return self::createTag('tr', implode('', $values), $attributes);
    }

    public static function drawTable(array $rows, $id = null, $wrap = false, $class = null, array $attributes = [])
    {
        if (!empty($rows))
        {
            $body = '';
            $headers = self::getHeaders(array_keys($rows[0]));
            $body .= self::tr($headers, true, ['class' => 'header']);
            foreach ($rows as $row)
            {
                self::checkRow($row, $attributes);
                $body .= self::tr($row, false, $attributes);
            }
            return (!$wrap) ? $body : self::table($body, ['id' => $id, 'class' => $class]);
        }
    }

    public static function checkRow(array &$row, array &$attributes)
    {
        // todo - refactoring this method
        if (isset($row['collected_sec']))
        {
            $attributes['collected'] = $row['collected_sec'];
            unset($row['collected_sec']);
        }
        if (isset($row['poll']))
        {
            $attributes['poll'] = $row['poll'];
            unset($row['poll']);
        }
        if (isset($row['status']) && isset($row['status_message']))
        {
            $row['status'] = $row['status_message'] . $row['status'];
            unset($row['status_message']);
        }
    }

    public static function getHeaders(array $headers)
    {
        // todo - refactoring this method
        if ($key = array_search('collected_sec', $headers))
        {
            unset($headers[$key]);
        }
        if ($key = array_search('poll', $headers))
        {
            unset($headers[$key]);
        }
        if ($key = array_search('status_message', $headers))
        {
            unset($headers[$key]);
        }
        return $headers;
    }

    public static function table($body, array $attributes = [])
    {
        return self::createTag('table', $body, $attributes);
    }

    public static function wrapTable($body, $id, $class = null)
    {
        $attributes = ['id' => $id];
        if (!empty($class))
        {
            $attributes['class'] = $class;
        }
        return self::createTag('table', $body, $attributes);
    }

    private static function drawRow(array $values, $head = false, $isParameters = false, array $attributes = [])
    {
        $row = '';
        $tagCell = ($head) ? 'th' : 'td';
        foreach ($values as $class => $value)
        {
            $value = self::createTag('span', $value, ['class' => ($isParameters) ? $values['parameter'] : $class]);
            $row .= self::createTag($tagCell, $value, ['class' => $class]);
        }
        return self::createTag('tr', $row, $attributes);
    }

    public static function drawMenu(array $menu, $id)
    {
        $last = array_pop($menu);
        $path = '';
        foreach ($menu as &$item)
        {
            if ($item != $menu[0])
            {
                $path .= $item . DIRECTORY_SEPARATOR;
            }
            $item = self::createLink(request::createUrl($path), $item);
            $item = self::createTag('li', $item);
        }
        if ($pos = strpos($last, '-'))
        { // todo: get array with values from ...
            if (in_array(substr($last, 0, $pos), ['process', 'file', 'filesystem', 'directory', 'host']))
            {
                $last = substr($last, ++$pos);
            }
        }
        array_push($menu, self::createTag('li', $last));
        return self::createTag('ul', implode('', $menu), ['id' => $id, 'class' => 'breadcrumb']);
    }

    public static function createLink($url, $text, array $attributes = [])
    {
        $attributes['href'] = $url;
        return self::createTag('a', $text, $attributes);
    }

    public static function createImg($src, array $attributes = [])
    {
        $attributes['src'] = $src;
        return self::createTag('img', null, $attributes);
    }

    public static function span($value, array $attributes = [])
    {
        return self::createTag('span', $value, $attributes);
    }

    public static function createSpan($value, array $attributes = [])
    {
        return self::createTag('span', $value, $attributes);
    }

    private static function createTag($tagName, $value = null, array $attributes = [])
    {
        $tag = '<' . $tagName;
        if (!empty($attributes))
        {
            foreach ($attributes as $attrName => $attrValue)
            {
                $tag .= ' ' . $attrName . '="' . $attrValue . '"';
            }
        }
        $tag .= (!empty($value) || ($value == 0)) ? '>' . $value . '</' . $tagName . '>' : ' />';
        return $tag;
    }

    public static function render(array $variables, $layout = self::LAYOUT)
    {
        return self::renderFile($layout, $variables);
    }

    /**
     * Отрендерить файл
     *
     * @param  string $fileName Название файла
     * @param array   $vars Массив переменных
     * @return string Файл в виде строки, если нет файла - null
     */
    private static function renderFile($fileName, array $vars)
    {
        $file = self::getPath($fileName);
        if (is_file($file))
        {
            ob_start();
            extract($vars);
            include($file);
            return ob_get_clean();
        }
        return null;
    }

    private static function getPath($filename)
    {
        return AutoLoader::getCoreDirectory() . self::VIEW_DIR . DIRECTORY_SEPARATOR . $filename . self::TPL_EXT;
    }

}