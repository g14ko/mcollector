<?php
/**
 * @author j3nya
 * @date 8/1/13
 * @time 3:27 PM
 */
namespace lib;

use lib\Sqlite as db;
use model\Collector as collector;
use model\collector\Server as server;

class Application
{
    const MENU = 'menu';
    const ASIDE = 'aside';
    const CONTENT = 'content';
    const EVENT = 'event';


    const JS_VARS = 'jsVars';
    const STYLES = 'styles';
    const SCRIPTS = 'scripts';
    const TITLE = 'title';
    const HOME = 'home';

    // api action names
    const COLLECT = 'collect';
    const UPDATE = 'update';
    const INIT_DB = 'init-db';
    const ACTION = 'action';
    const ACTIONS = 'actions';

    // api method names
    const COLLECTOR = 'collector';
    const UPDATER = 'updater';
    const INITIALIZER_DB = 'initDB';

    // key in globals for raw post data
    const HTTP_RAW_POST_DATA = 'HTTP_RAW_POST_DATA';

    private $params = [];
    private $actions = [];

    private static $api = [
        self::COLLECT => self::COLLECTOR,
        self::UPDATE  => self::UPDATER,
        self::INIT_DB => self::INITIALIZER_DB,
        self::ACTION  => self::ACTION,
        self::EVENT   => self::EVENT
    ];

    public static function create()
    {
        return new self();
    }

    public function run()
    {
        $params = request::getParams();
        if (!self::execApi($params))
        {
            self::execute($this, $params);
        }
    }

    /* actions */

    private function servers()
    {
        $servers = server::getServers(self::getFor(__FUNCTION__));
        foreach ($servers as &$server)
        {
            if ($alias = self::extractAlias($server))
            {
                self::correctTimeCollected($server);
                self::addServerLink($server, $alias);
                self::addUpdateLink($server, $alias);
            }
        }
        return view::drawTable($servers, 'servers', true);
    }

    private function services($alias, $table = null)
    {
        $existsUrl = self::checkIssetUrlOptions($alias);
        foreach (server::getServices($alias, self::getFor(__FUNCTION__)) as $type => $service)
        {
            if (!empty($service))
            {
                foreach ($service as &$options)
                {
                    self::addServiceLinks($alias, $type, $options, $existsUrl);
                    self::setStatus($type, $options);
                }
                $table .= view::drawTable($service);
            }
        }
        return view::table($table, ['id'=>'services', 'poll' => server::getPollTime($alias)]);
    }

    private function options($alias, $service)
    {
        list($type, $name) = explode('-', $service);
        $parameters = server::getOptions($alias, $type, $name, self::getFor(__FUNCTION__));
        return view::drawTable($parameters, 'parameters', true, $type);
    }

    private function menu(array $params = [])
    {
        $menu = [view::createImg(image::getAbsPath(image::HOME), ['class' => 'home'])];
        return View::drawMenu(array_merge($menu, $params), 'menu');
    }

    /* api */

    private static function initDB()
    {
        return db::init();
    }

    private static function updater($alias, $service = null)
    {
        if ($server = config::get([server::SERVERS, $alias]))
        {
            if ($xml = request::send($server))
            {
                response::save($xml);
                if (response::is())
                {
                    server::save(response::getAlias(), response::get(), server::V_5_1_1);
                }
            }
        }
        $response = ['server' => $alias];
        if (isset($_POST['action']))
        {
            $response['action'] = $_POST['action'];
        }
        if (isset($_POST['type']) && isset($_POST['service']))
        {
            if ($service = server::getTimeCollected($alias, [$_POST['type']], $_POST['service']))
            {
                $response['service'] = $service[0];
            }
        }

        else
        {
            $services = [];

            if (isset($_POST['servers']))
            {
                $services = array_merge(
                    $services,
                    server::getTimeCollected(
                          $alias,
                          array_keys(config::get([$_POST['servers'] . '-' . 'servers', 'tables']))
                    )
                );
            }
            if (isset($_POST['services']))
            {
                $services = array_merge(
                    $services,
                    server::getTimeCollected(
                          $alias,
                          array_keys(config::get([$_POST['services'] . '-' . 'services', 'tables']))
                    )
                );
            }
            $response['services'] = $services;
        }
        return print(json_encode($response));
    }

    private static function collector()
    {
        if (!empty($GLOBALS) && array_key_exists(self::HTTP_RAW_POST_DATA, $GLOBALS))
        {
            self::saveXML($GLOBALS[self::HTTP_RAW_POST_DATA]);
        }
        return true;
    }

    private static function action($alias = null, $service = null, $action = null)
    {
        $response = [
            'server'  => $alias,
            'service' => $service,
            'action'  => $action
        ];
        if ($options = config::get([server::SERVERS, $alias]))
        {
            request::action($options, $service, $action);
        }
        else
        {
            $response['message'] = 'no isset options for ' . $alias . ' in config';
        }

        return print(json_encode($response));
    }

    private static function event($alias)
    {
        $response = ['server' => $alias];
        if (isset($_POST['service']))
        {
            $response['service'] = $_POST['service'];
        }
        if (isset($_POST['action']))
        {
            $response['action'] = $_POST['action'];
        }
        return print(json_encode(array_merge($response, server::getEvents($alias))));
    }

    /* private methods */

    private static function checkIssetUrlOptions($alias)
    {
        $url = config::get([server::SERVERS, $alias]);
        return isset($url['ssl']) && isset($url['host']) && isset($url['port']) && isset($url['user']) && isset($url['pass']);
    }

    private static function saveXML($xml)
    {
        if (!empty($xml))
        {
            response::save($xml);
            if (response::is())
            {
                server::save(response::getAlias(), response::get());
            }
        }
    }

    private static function addServerLink(array &$server, $alias)
    {
        $server[server::SERVER] = view::createLink(request::createUrl($alias), $alias, ['id' => $alias]);
    }

    private static function addUpdateLink(array &$server, $alias)
    {
        $server[server::UPDATE] = view::createLink(
                                      request::createUrl([self::UPDATE, $alias]),
                                      view::createImg(image::getAbsPath(image::UPDATE)),
                                      [
                                          'id'    => self::UPDATE . '-' . $alias,
                                          'class' => !config::issetSection([server::SERVERS], $alias) ?
                                                  implode(' ', [$alias, view::LINK_UPDATE, view::LINK_INACTIVE]) :
                                                  implode(' ', [$alias, view::LINK_UPDATE, view::LINK_ACTIVE])
                                      ]
        );
    }

    private static function correctTimeCollected(array &$server, $oldKey = 'collected_sec', $newKey = 'collected')
    {
        $server[$newKey] = !empty($server[$oldKey]) ? DateTime::getInterval($server[$oldKey]) : 'not specified time collected';
    }

    private static function extractAlias(array $server)
    {
        return !empty($server) && array_key_exists(server::SERVER, $server) ? $server[server::SERVER] : null;
    }

    private static function execute(Application $application, array $params = [])
    {
        $application->setAction($params);
        (!request::isPost()) ? $application->render() : $application->send();
    }

    private static function execApi(array $params = [])
    {
        $method = !empty($params) ? array_shift($params) : null;
        if (empty($method) || !array_key_exists($method, self::$api))
        {
            return false;
        }
        if (!method_exists(__CLASS__, self::$api[$method]))
        {
            return 'No isset api method';
        }
        !empty($params) ? : $params = [null];
        return call_user_func_array([__CLASS__, self::$api[$method]], $params);
    }

    private function send()
    {
        $response = [
            self::MENU    => $this->get(self::MENU),
            self::ASIDE   => $this->get(self::ASIDE),
            self::CONTENT => $this->get(self::CONTENT)
        ];
        echo json_encode($response);
    }

    private function render()
    {
        $vars = [
            self::JS_VARS => $this->getJsVariables(),
            self::STYLES  => $this->getSources(self::STYLES),
            self::SCRIPTS => $this->getSources(self::SCRIPTS),
            self::TITLE   => $this->getTitle(),
            self::MENU    => $this->get(self::MENU),
            self::ASIDE   => $this->get(self::ASIDE),
            self::CONTENT => $this->get(self::CONTENT)
        ];
        view::render($vars);
    }

    private function getTitle()
    {
        return config::get(['head', 'title']);
    }

    private function getJsVariables()
    {
        return [
            'interval'     => config::get(['refresh', 'interval']) * 1000,
            'periods' => config::get(['refresh', 'periods']),
            'lastModified' => db::lastModifiedDb() * 1000
        ];
    }

    private function getSources($name)
    {
        $sources = config::get([$name]);
        if (!empty($sources))
        {
            foreach ($sources as &$source)
            {
                $source = request::createUrl($source);
            }
        }
        return $sources;
    }

    private function get($name)
    {
        if (!method_exists($this, $this->actions[$name]))
        {
            return false;
        }
        return call_user_func_array([$this, $this->actions[$name]], $this->params[$name]);
    }

    private function setAction(array $params = [])
    {
        switch (count($params))
        {
            case 1 :
                $this->setActionParams(self::MENU, 'menu', [$params]);
                $this->setActionParams(self::ASIDE, 'servers');
                $this->setActionParams(self::CONTENT, 'services', $params);
                break;
            case 2 :
                $this->setActionParams(self::MENU, 'menu', [$params]);
                $this->setActionParams(self::ASIDE, 'services', [$params[0]]);
                $this->setActionParams(self::CONTENT, 'options', $params);
                break;
            default:
                $this->setActionParams(self::MENU, 'menu', [$params]);
                $this->setActionParams(self::ASIDE, '');
                $this->setActionParams(self::CONTENT, 'servers');
                break;
        }
    }

    private function setActionParams($name, $action, array $params = [])
    {
        $this->actions[$name] = $action;
        $this->params[$name] = $params;
    }

    private function isContent($action)
    {
        return $this->actions[self::CONTENT] == $action;
    }

    private function getFor($action)
    {
        return !in_array($action, $this->actions) ? '' : array_search($action, $this->actions);
    }

    private function addServiceLinks($alias, $type, &$options, $existsUrl)
    {
        $service = $options[$type];
        $options[$type] = view::createLink(
                              request::createUrl([$alias, $type . '-' . $service]),
                              $service,
                              view::getServiceAttributes($service)
        );
        if (server::isProcessType($type))
        {
            self::setActionLinks($alias, $service, $options, $existsUrl);
        }
        self::setMonitorLink($alias, $service, $options, $existsUrl);
    }

    private function setActionLinks($alias, $service, &$options, $existsUrl)
    {
        if ($options[server::MONITOR] == server::MONITORED)
        {
            switch (true)
            {
                case ($options[server::STATUS] == server::STATUS_RUNNING):
                    $options[view::ACTIONS] = view::createLink(
                                                  request::createUrl([self::ACTION, $alias, $service, server::RESTART]),
                                                  view::createImg(image::getAbsPath(image::RESTART)),
                                                  view::getActionAttributes($alias, $service, server::RESTART, $existsUrl)
                    );
                    $options[view::ACTIONS] .= view::createLink(
                                                   request::createUrl([self::ACTION, $alias, $service, server::STOP]),
                                                   view::createImg(image::getAbsPath(image::STOP)),
                                                   view::getActionAttributes($alias, $service, server::STOP, $existsUrl)
                    );
                    break;
                case ($options[server::STATUS] == server::STATUS_DOES_NOT_EXISTS):
                    $options[view::ACTIONS] = view::createLink(
                                                  request::createUrl([self::ACTION, $alias, $service, server::START]),
                                                  view::createImg(image::getAbsPath(image::START)),
                                                  view::getActionAttributes($alias, $service, server::START, $existsUrl)
                    );
                    break;
            }
        }
    }

    private function setMonitorLink($alias, $service, &$options, $existsUrl)
    {
        isset($options[view::ACTIONS]) || $options[view::ACTIONS] = null;
        if (isset($options[server::MONITOR]))
        {
            $action = server::getMonitorAction($options[server::MONITOR]);
            $image = server::getMonitorImage($options[server::MONITOR]);
            $options[view::ACTIONS] .= view::createLink(
                                           request::createUrl([self::ACTION, $alias, $service, $action]),
                                           view::createImg(
                                               !is_array($image) ?
                                                   image::getAbsPath($image) :
                                                   image::getAbsPath($image['name'], $image['extension'])
                                           ),
                                           view::getActionAttributes($alias, $service, $action, $existsUrl)
            );
        }
    }

    private function setStatus($type, &$options)
    {
        switch (true)
        {
            case $options[server::MONITOR] == server::MONITORED:
                if (!empty($options[server::STATUS_MESSAGE]))
                {
                    $options[server::STATUS_MESSAGE] = view::createSpan($options[server::STATUS_MESSAGE], ['class' => 'hidden warning']);
                    $options[server::STATUS_MESSAGE] .= view::createImg(image::getAbsPath(image::WARNING), ['class' => 'warning']);

                }
                $status = server::getStatusByType($type, $options[server::STATUS]);
                $options[server::STATUS] = view::createSpan(
                                               $status,
                                               ['class' => server::getClassByStatus($status)]
                );
                break;
            case $options[server::MONITOR] == server::NOT_MONITORED:
                // todo: move this message somewhere
                $options[server::STATUS] = view::createSpan('not monitored', ['class' => 'orange']);
                break;
            case $options[server::MONITOR] == server::INITIALIZATION:
                // todo: move this message somewhere
                $options[server::STATUS] = view::createSpan('initialization', ['class' => 'blue']);
                break;

        }
        unset($options[server::MONITOR]);
    }

}