<?php
// index.php
require '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App;

$container = $app->getContainer();

$container['db'] = function () {
    $database = new Database();
    return $database->getConnection();
};

require 'register.php';

$app->run();
?>