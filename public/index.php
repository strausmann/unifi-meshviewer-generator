<?php

use Dotenv\Dotenv;
use ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator;

// load the class using the composer autoloader
require_once '../vendor/autoload.php';

// load Whoops
if (class_exists("\Whoops\Run")) {
    $whoops = new \Whoops\Run();
    $whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler());
    $whoops->register();
}

// load dotenv and the .env file.
$dotenv = Dotenv::createImmutable(dirname(dirname(__FILE__)));
$dotenv->load();

// set TimeZone
date_default_timezone_set($_ENV['TIMEZONE']);

// Initiate the MeshviewerGenerator
$meshGenerator = new MeshviewerGenerator();

// set Content Type to application/json
header('Content-Type: application/json');

// Generate the JSON Files
$status = $meshGenerator->executeTask();

echo $status;
