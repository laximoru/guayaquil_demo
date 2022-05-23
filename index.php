<?php

use guayaquil\Config;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/vendor/autoload.php');

$configData = @file_get_contents(__DIR__ . '/config.json');
if ($configData) {
    $configData = json_decode($configData, true);

    $configuration = new Config($configData);
    Config::setConfig($configuration);
}

require_once(__DIR__ . '/com_guayaquil/index.php');