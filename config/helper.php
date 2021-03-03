<?php
/**
 * Gets the value of the $variable of an .env file.
 * @param string $variable The variable to get or set.
 * @return mixed The value of the $variable.
 */
function config($variable)
{
    $dotenv = \Dotenv\Dotenv::createImmutable("../");
    $config = $dotenv->load();

    return isset($config[$variable]) ? $config[$variable] : null;
}