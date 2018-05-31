<?php

define('BASE_PATH',realpath(__DIR__));

require 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(BASE_PATH);
$dotenv->load();

