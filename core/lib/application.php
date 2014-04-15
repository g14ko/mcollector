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
    // parts of site
    const MENU = 'menu';
    const ASIDE = 'aside';
    const CONTENT = 'content';

    const EVENT = 'event';
    const JS_VARS = 'jsVars';
    const STYLES = 'styles';
    const SCRIPTS = 'scripts';
    const TITLE = 'title';

    // external action names
    const COLLECT = 'collect';
    const UPDATE = 'update';
    const INIT_DB = 'init-db';
    const ACTION = 'action';
    const ACTIONS = 'actions';

    // external action method names
    const COLLECTOR = 'collector';
    const UPDATER = 'updater';
    const INITIALIZER_DB = 'initDB';

    // key in globals for raw post data
    const HTTP_RAW_POST_DATA = 'HTTP_RAW_POST_DATA';

    // internal actions and params
    private $params = [];
    private $actions = [];

    // names externals actions
    private static $external = [
        self::COLLECT => self::COLLECTOR,
        self::UPDATE  => self::UPDATER,
        self::INIT_DB => self::INITIALIZER_DB,
        self::ACTION  => self::ACTION,
        self::EVENT   => self::EVENT
    ];

    /**
     * Возвращает обьект модели приложения
     *
     * @return Application Обьект модели приложения
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Запуск приложения
     */
    public function run()
    {
        $params = request::getParams();
        !self::execAction($params) && self::execute($this, $params);
    }

    /* internal actions */

    /**
     * Возвращает список серверов
     *
     * @return string Список серверов
     */
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

    /**
     * Возвращает список сервисов сервера
     *
     * @param string $alias Алиас названия сервера
     * @param string $table Таблица для добавления сервисов (пустая)
     * @return string Список сервисов сервера
     */
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
        return view::table($table, ['id' => 'services', 'poll' => server::getPollTime($alias)]);
    }

    /**
     * Возвращает список опций сервиса
     *
     * @param string $alias Алиас названия сервера
     * @param string $service Название сервиса
     * @return string Список опций сервиса
     */
    private function options($alias, $service)
    {
        list($type, $name) = explode('-', $service);
        $parameters = server::getOptions($alias, $type, $name, self::getFor(__FUNCTION__));
        return view::drawTable($parameters, 'parameters', true, $type);
    }

    /**
     * Возвращает меню
     *
     * @param array $items Список пунктов меню
     * @return string Меню
     */
    private function menu(array $items = [])
    {
        $menu = [view::createImg(image::getAbsPath(image::HOME), ['class' => 'home'])];
        return View::drawMenu(array_merge($menu, $items), 'menu');
    }

    /* external actions */

    /**
     * Инициализация БД
     *
     * @return bool Признак успешной инициализации БД
     */
    private static function initDB()
    {
        return db::init();
    }

    /**
     * Обновить данные по серверу
     *
     * @param string $alias Алиас названия сервера
     * @param string $service Название сервиса
     * @return bool
     */
    private static function updater($alias, $service = null)
    {
        if ($server = config::get([server::SERVERS, $alias]))
        {
            if ($xml = request::send($server))
            {
                response::save($xml);
                response::is() && server::save(response::getAlias(), response::get(), server::V_5_1_1);
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

    /**
     * Сохранить данные сервера из необработанных POST-данных
     *
     * @return bool
     */
    private static function collector()
    {
        if (!empty($GLOBALS) && isset($GLOBALS[self::HTTP_RAW_POST_DATA]))
        {
            self::saveXML($GLOBALS[self::HTTP_RAW_POST_DATA]);
        }
        return true;
    }

    /**
     * Выполнить действие на сервере
     *
     * @param string $alias Алиас названия сервера
     * @param string $service Название сервиса
     * @param string $action Название  действия
     * @return int
     */
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

    /**
     * Получить события для сервера
     *
     * @param string $alias Алиас названия сервера
     * @return int
     */
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

    /**
     * Проверяет заданы ли опции УРЛ для сервера в конфиге
     *
     * @param string $alias Алиас названия сервера
     * @return bool Заданы ли УРЛ опции в конфиге
     * <dl>
     *  <dt>true</dt><dd>да</dd>
     *  <dt>false</dt><dd>нет</dd>
     * </dl>
     */
    private static function checkIssetUrlOptions($alias)
    {
        $url = config::get([server::SERVERS, $alias]);
        return isset($url['ssl']) && isset($url['host']) && isset($url['port']) && isset($url['user']) && isset($url['pass']);
    }

    /**
     * Сохранить XML документ
     *
     * @param string $xml XML документ
     * @return void
     */
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

    /**
     * Добавить ссылку для сервера
     *
     * @param array  $server Данные сервера
     * @param string $alias Алиас названия сервера
     * @return void
     */
    private static function addServerLink(array &$server, $alias)
    {
        $server[server::SERVER] = view::createLink(request::createUrl($alias), $alias, ['id' => $alias]);
    }

    /**
     * Добавить ссылку для обновления сервера
     *
     * @param array  $server Данные сервера
     * @param string $alias Алиас названия сервера
     * @return void
     */
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

    /**
     * Трансформация временной метки в интервал для отображения
     *
     * @param array  $server Данные сервера
     * @param string $oldKey Старый ключ временной метки
     * @param string $newKey Новый ключ для интервала
     * @return void
     */
    private static function correctTimeCollected(array &$server, $oldKey = 'collected_sec', $newKey = 'collected')
    {
        $server[$newKey] = !empty($server[$oldKey]) ? DateTime::getInterval($server[$oldKey]) : 'not specified time collected';
    }

    /**
     * Извлечь алиас сервера
     *
     * @param array $server Данные сервера
     * @return string Алиас сервера (null в противном случае)
     */
    private static function extractAlias(array $server)
    {
        return !empty($server) && array_key_exists(server::SERVER, $server) ? $server[server::SERVER] : null;
    }

    /**
     * Выполнить приложение
     *
     * @param Application $application Объект приложения
     * @param array       $params Список параметров
     * @return void
     */
    private static function execute(Application $application, array $params = [])
    {
        $application->setAction($params);
        (!request::isPost()) ? $application->render() : $application->send();
    }

    /**
     * Выполнить внешнее действие приложения
     *
     * @param array $params
     * @return bool|mixed|string
     */
    private static function execAction(array $params = [])
    {
        $method = !empty($params) ? array_shift($params) : null;
        if (empty($method) || !isset(self::$external[$method]))
        {
            return false;
        }
        if (!method_exists(__CLASS__, self::$external[$method]))
        {
            return 'No method ' . self::$external[$method]; // todo: throw the exception
        }
        return call_user_func_array([__CLASS__, self::$external[$method]], !empty($params) ? $params : [null]);
    }

    /**
     * Отправить ответ для AJAX запроса
     *
     * @return void
     */
    private function send()
    {
        $response = [
            self::MENU    => $this->get(self::MENU),
            self::ASIDE   => $this->get(self::ASIDE),
            self::CONTENT => $this->get(self::CONTENT)
        ];
        echo json_encode($response);
    }

    /**
     * Отправить ответ для запроса
     *
     * @return void
     */
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
        echo view::render($vars);
    }

    /**
     * Получить загаловок для страницы
     *
     * @return string Загаловок страницы (null, если загаловок не найден)
     */
    private function getTitle()
    {
        return config::get(['head', 'title']);
    }

    /**
     * Получить переменные для js
     *
     * @return array Переменные для js
     * <dl>
     *  <dt>(int) interval</dt><dd>Интервал для обновления страницы, в секундах</dd>
     *  <dt>(int) periods</dt><dd>Период для обновления страницы, в секундах</dd>
     *  <dt>(int) lastModified</dt><dd>Время последнего обновления файла БД, в секундах</dd>
     * </dl>
     */
    // todo: fixed doc blocks
    private function getJsVariables()
    {
        return [
            'interval'     => config::get(['refresh', 'interval']) * 1000,
            'periods'      => config::get(['refresh', 'periods']),
            'lastModified' => db::lastModifiedDb() * 1000
        ];
    }

    /**
     * Получить источники для скриптов и стилей
     *
     * @param $name Название скрипта или стиля
     * @return Источник файла скрипта или стиля (null, если источник не найден)
     */
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

    /**
     * Возвращает содержание для части сайта
     *
     * @param string $name Название части сайта
     * @return string Содержание для части сайта
     */
    private function get($name)
    {
        return !method_exists($this, $this->actions[$name]) ? null :
            call_user_func_array([$this, $this->actions[$name]], $this->params[$name]);
    }

    /**
     * Задать название экшена и параметров для различных частей сайта
     *
     * @param array $params Список параметров
     * @return void
     */
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

    /**
     * Задать название экшена и параметров для заданной части сайта
     *
     * @param string $name Название части сайта
     * @param string $action Название экшена
     * @param array  $params Список параметров
     * @return void
     */
    private function setActionParams($name, $action, array $params = [])
    {
        $this->actions[$name] = $action;
        $this->params[$name] = $params;
    }

    /**
     * Получить название части сайта, для которой применён экшен
     *
     * @param string $action Название экшена
     * @return string Название части сайта
     */
    private function getFor($action)
    {
        return !in_array($action, $this->actions) ? '' : array_search($action, $this->actions);
    }

    /**
     * Добавить ссылки на сервисы сервера
     *
     * @param $alias Алиас названия сервера
     * @param $type Тип сервиса
     * @param $options Опции сервиса
     * @param $existsUrl Заданы ли опции УРЛ для сервера в конфиге
     * @return void
     */
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

    /**
     * Добавить ссылки на экшены сервера
     *
     * @param $alias Алиас названия сервера
     * @param $service Названия сервиса
     * @param $options Опции сервиса
     * @param $existsUrl Заданы ли опции УРЛ для сервера в конфиге
     * @return void
     */
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

    /**
     * Добавить ссылки на экшен (монитор/анмонитор) сервера
     *
     * @param $alias Алиас названия сервера
     * @param $service Названия сервиса
     * @param $options Опции сервиса
     * @param $existsUrl Заданы ли опции УРЛ для сервера в конфиге
     * @return void
     */
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

    /**
     * Задать статус сервиса
     *
     * @param $type Тип сервиса
     * @param $options Опции сервиса
     */
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