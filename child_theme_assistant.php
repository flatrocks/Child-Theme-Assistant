<?php  
/* 
Plugin Name: Child Theme Assistant
Plugin URI: 
Description: Plugin for creating and managing child themes
Author: T. Wilson
Version: 0.0
Author URI: http://www.rollnorocks.com
*/  

require_once 'child_theme_assistant_class.php';

add_action('admin_menu', 'ChildThemeAssistant::add_menu_items');
add_action('admin_head', 'ChildThemeAssistant::register_head');
