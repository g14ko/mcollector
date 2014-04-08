<?php
/**
 * @author j3nya
 * @date 8/1/13
 * @time 12:52 PM
 */

namespace lib;

class Request
{
    const HTTP = 'http://';
    const HTTPS = 'https://';
    const POST = 'POST';

    const SSL = 'ssl';
    const HOST = 'host';
    const PORT = 'port';
    const USER = 'user';
    const PASS = 'pass';

    private static $index = 'index.php';
    private static $subDir = '';
    private static $slash = '/';
    private static $colon = ':';
    private static $query = '_status?format=xml';

    // cUrl handle
    private static $ch;

    private static $options = [];
    private static $response;

    public static function action(array $options, $service, $action)
    {
        self::cleanOptions();
        self::setActionOptions();
        self::setUrl($options, $service);
        self::setAuth($options);
        self::setActionParams($action);
        self::execute();
        return self::$response;
    }

    private static function cleanOptions()
    {
        self::$options = [];
    }

    private static function setActionOptions()
    {
        self::$options[\CURLOPT_HEADER] = false;
        self::$options[\CURLOPT_POST] = true;
        self::$options[\CURLOPT_RETURNTRANSFER] = true;
    }

    private static function setUrl(array $options, $service)
    {
        self::$options[\CURLOPT_URL] = self::collectUrl($options[self::SSL], $options[self::HOST], $options[self::PORT], $service);
    }

    private static function setAuth(array $options)
    {
        self::$options[\CURLOPT_USERPWD] = self::collectAuth($options[self::USER], $options[self::PASS]);
    }

    private static function setActionParams($action)
    {
        self::$options[\CURLOPT_POSTFIELDS] = 'action=' . $action;
    }

    public static function send(array $options = [])
    {
        self::clean();
        self::setOptions(self::getServerOptions($options));
        self::execute();
        if (!self::$response)
        {
            throw new \Exception('No response');
        }
        return self::$response;
    }

    private static function clean()
    {
        self::$response = null;
    }

    private static function setSubDirectory()
    {
        self::$subDir = (strpos($_SERVER['SCRIPT_NAME'], self::$index) !== false) ?
            str_replace(self::$index, '', $_SERVER['SCRIPT_NAME']) :
            $_SERVER['SCRIPT_NAME'];
        self::$subDir = trim(self::$subDir, self::$slash);
    }

    private static function issetSubDirectory()
    {
        return (bool)self::$subDir;
    }

    public static function getParams()
    {
        self::setSubDirectory();
        $params = (!self::issetSubDirectory()) ?
            trim($_SERVER['REQUEST_URI'], self::$slash) :
            (strpos($_SERVER['REQUEST_URI'], self::$subDir) === false) ?
                : trim(str_replace(self::$subDir, '', $_SERVER['REQUEST_URI']), self::$slash);
        $params = trim($params, self::$slash);
        return array_filter(explode(self::$slash, $params));
    }

    public static function createUrl($path)
    {
        !is_array($path) ? : $path = implode('/', $path);
        return self::getProtocol() . self::getHost() . $path;
    }

    public static function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] === self::POST);
    }

    public static function getSubDirectory()
    {
        return self::$subDir;
    }

    private static function getProtocol()
    {
        return self::$slash . self::$slash;
    }

    private static function getHost()
    {
        $host = $_SERVER['HTTP_HOST'] . self::$slash;
        $host .= (!self::issetSubDirectory()) ? '' : self::$subDir . self::$slash;
        return $host;
    }

    private static function setOptions(array $options)
    {
        self::$options = [
            \CURLOPT_URL            => $options['url'],
            \CURLOPT_HEADER         => false, // без заголовков ответа
            \CURLOPT_RETURNTRANSFER => true
        ];
        if (isset($options['auth']))
        {
            self::$options[\CURLOPT_USERPWD] = $options['auth'];
        }
    }

    private static function execute()
    {
        self::$ch = \curl_init();
        \curl_setopt_array(self::$ch, self::$options);
        self::$response = \curl_exec(self::$ch);
        \curl_close(self::$ch);
    }

    private static function getServerOptions(array $options)
    {
        if (isset($options['url']) && $options['url'])
        {
            return ['url' => $options['url']];
        }
        return [
            'url'  => self::collectUrl($options['ssl'], $options['host'], $options['port']),
            'auth' => self::collectAuth($options['user'], $options['pass'])
        ];
    }

    private static function collectUrl($ssl, $host, $port, $query = null)
    {
        $url = (!$ssl) ? self::HTTP : self::HTTPS;
        $url .= $host . ':' . $port;
        $query = !$query ? self::$query : $query;
        $url .= self::$slash . $query;
        return $url;
    }

    private static function collectAuth($user, $pass)
    {
        return $user . self::$colon . $pass;
    }

}