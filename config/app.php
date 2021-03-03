<?php
require_once "../vendor/autoload.php";
$dotenv = \Dotenv\Dotenv::createImmutable("../");
return $dotenv->load();