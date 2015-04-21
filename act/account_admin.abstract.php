<?php
/*!
 * @file		account.abstract.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-16-2015
 * @section DESCRIPTION
 * This file contains the abstract accounts class as well as the init function
 * to load the user account into the system
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

/*! Accounts Admin Abstract Class */
abstract class account_admin{
	
	abstract public function act_users($user_q,$fields=false,$extended=false);
	
}