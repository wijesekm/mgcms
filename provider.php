<?php
/*!
 * @file		provider.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-11-2015
 * @section DESCRIPTION
 * This file is the main interface point for provider content access
 * All variable clauses can be used with this.
 *
 * ###################################
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/.
 * ###################################
 */
 
define('INIT',true);
define('LOAD_TYPE',1);
include(dirname(__FILE__).'/cfg/ini.conf');
include(dirname(__FILE__).'/cfg/conf'.PHPEXT);
include($GLOBALS['MG']['CFG']['PATH']['INC'].'/init/init'.PHPEXT);