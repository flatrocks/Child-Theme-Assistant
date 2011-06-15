<?php  
/* 
Plugin Name: Child Theme Assistant
Plugin URI: 
Description: Plugin for creating and managing child themes
Author: T. Wilson
Version: 0.1
Author URI: http://www.rollnorocks.com
License: GPL2
*/

/*
    Copyright 2011  Roll No Rocks LLC  (email : tom@rollnorocks.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You can see the GNU General Public License at 
    http://www.gnu.org/licenses/gpl-2.0.html,
    or write to the Free Software Foundation, Inc., 
    51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once 'class-child-theme-assistant.php';

add_action('admin_menu', 'ChildThemeAssistant::add_menu_items');
add_action('admin_init', 'ChildThemeAssistant::init');

