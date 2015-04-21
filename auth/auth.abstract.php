<?php
/*!
 * @file		auth.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-27-2015
 * @section DESCRIPTION
 * This file contains the abstract class template for all
 * authentication methods.
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
 
if(!defined('INIT')){
	die();
}

/*! AUTH Class */
abstract class auth{
		
	//public vars
	
	//private vars

	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	abstract public function auth_authenticate($userId,$password);
	abstract public function auth_changePassword($password);
	abstract public function auth_canChangePassword();
	
}
