<?php
/**
 * @author j3nya
 * @date 7/30/13
 * @time 12:44 PM
 */

use lib\AutoLoader as loader;
use lib\Application as app;

define('DEBUG', true);

require_once('../core/lib/autoloader.php');

loader::init(__DIR__);

app::create()->run();