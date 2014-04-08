<?php
/**
 * @author j3nya
 * @date 7/30/13
 * @time 12:44 PM
 */

require_once('../core/lib/autoloader.php');

use lib\AutoLoader as loader;
use lib\Application as app;

define('DEBUG_MODE', true);
loader::init(__DIR__);

app::create()->run();