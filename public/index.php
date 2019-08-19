<?php

use ISPServerfarm\UnifiMeshviewer\MeshviewerGenerator;
use Dotenv\Dotenv;
use UniFi_API\Client;

// load the class using the composer autoloader
require_once('../vendor/autoload.php');

if (class_exists("\Whoops\Run")){
    $whoops = new \Whoops\Run;
    $whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

$dotenv = Dotenv::create(dirname(dirname(__FILE__)));
$dotenv->load(true);

if (file_exists($_SERVER['DOCUMENT_ROOT'].'/composer.json')) {
  // ...
  ech;
}

var_dump(class_exists("\Whoops\Run"));

echo $google;

echo $projectRootPath = realpath(\Composer\Factory::getComposerFile());

print_r(pathinfo(dirname(\Composer\Factory::getComposerFile())));
print_r($_SERVER);

date_default_timezone_set(getenv('TIMEZONE'));
header('Content-Type: application/json');


$meshGenerator = new MeshviewerGenerator();
#$meshGenerator->enableDebug();
#echo "<pre>";
#print_r($meshGenerator->buildNodes());
#exit();

#print_r($meshGenerator->getAccessPointMetaDataBySerial('18E829E6D74F'));

#print_r($meshGenerator->outputMeshviewerList());

#print_r($meshGenerator->outputNodelist());

$meshGenerator->writeNodeListFile();

$meshGenerator->writeMeshviewerListFile();

#print_r($meshGenerator->getLinks());

?>