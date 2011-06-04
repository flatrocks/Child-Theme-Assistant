<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

require_once 'PHP/CompatInfo.php';

$source = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'child_theme_assistant.php';

$info = new PHP_CompatInfo();
echo '<pre>';
$info->parseFile($source);
echo '</pre>';
// you may also use unified method:  $info->parseData($source);