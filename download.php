<?php
$wp_root = dirname(__FILE__) .'/../../../';
if(file_exists($wp_root . 'wp-load.php')) {
  require_once($wp_root . "wp-load.php");
} else {
  exit;
}
require_once 'class-child-theme-assistant.php';

ChildThemeAssistant::execute_download();
