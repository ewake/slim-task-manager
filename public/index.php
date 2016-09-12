<?php
define('_ROOT', dirname(__DIR__).'/private');
define('_BOOT', __DIR__);

$app = require_once _ROOT . '/bootstrap.php';

$app->run();
