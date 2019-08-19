<?php

use ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator;
use Dotenv\Dotenv;

// load the class using the composer autoloader
require_once('../vendor/autoload.php');

// load Whoops
if (class_exists("\Whoops\Run")){
    $whoops = new \Whoops\Run;
    $whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

// load dotenv and the .env file.
$dotenv = Dotenv::create(dirname(dirname(__FILE__)));
$dotenv->load(true);

// set TimeZone
date_default_timezone_set(getenv('TIMEZONE'));

// set Content Type to application/json
header('Content-Type: application/json');

// Initiate the MeshviewerGenerator
$meshGenerator = new MeshviewerGenerator();

// generate the NodeList JSON file in the data/ folder
$meshGenerator->writeNodeListFile();

// generate the Meshviewer JSON file in the data/ folder
$meshGenerator->writeMeshviewerListFile();

?>