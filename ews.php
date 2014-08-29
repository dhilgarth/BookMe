<?php

require_once 'php-ews/EWS_Exception.php';
require_once 'php-ews/EWSWrapper.php';

function __autoload($class_name)
{
    // Start from the base path and determine the location from the class name,
    $base_path = 'php-ews';
    $include_file = $base_path . '/' . str_replace('_', '/', $class_name) . '.php';

    return (file_exists($include_file) ? require_once $include_file : false);
}

$server = 'outlook.office365.com';
$username = '<username>';
$password = '<password>';

$ews = new EWSWrapper($server, $username, $password);

?>