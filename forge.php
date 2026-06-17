#!/usr/bin/env php
<?php
declare(strict_types=1);

define("BASE_PATH", __DIR__);
require_once BASE_PATH . "/kernel/Core/Support/helpers.php";

use Forge\Core\Bootstrap\Bootstrap;
use Forge\Core\DI\Container;
use Forge\CLI\Application;
use Forge\Core\Autoloader;
use Forge\Core\Config\EnvParser;
use Forge\Core\Debug\Metrics;

require BASE_PATH . "/kernel/Core/Autoloader.php";
require BASE_PATH . "/kernel/Core/Config/EnvParser.php";

Autoloader::register();
EnvParser::load(BASE_PATH . "/.env");

ini_set('display_errors', '1');
error_reporting(E_ALL);

$container = Container::getInstance();
Metrics::start('cli_resolution');
$container = Bootstrap::initCliContainer();

$app = $container->get(Application::class);
Metrics::stop('cli_resolution');
exit($app->run($argv));
