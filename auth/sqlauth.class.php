<?php
/*!
 * @file		sqlauth.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-27-2015
 * @section DESCRIPTION
 * This file contains the class for SQL database based authentication
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

/*! SQL Auth Class */
class sqlauth{
		
	//public vars
	
	//private vars

	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */

	/*!
	 * This function authenticates a user
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $userId User ID to check
	 * @param $password Password to check
	 *
	 * @return true on authenticate, false if no authenication
	 */
	final public function auth_authenticate($userId,$password){
		if($userId!=$GLOBALS['MG']['USER']->act_get('id')){
			trigger_error('(sqlauth): Entered user ID does not match current loaded account',E_USER_WARNING);
			return false;
		}
		if(!$pwd = $GLOBALS['MG']['USER']->act_get('password')){
			trigger_error('(sqlauth): No user password set in database!',E_USER_WARNING);
			return false;
		}
		$hash = new hash();
		return $hash->cr_verifyPasswordHash($password,$pwd);
	}

	/*!
	 * This function changes a users password
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $password Password to change
	 *
	 * @return true on success, false on failure
	 */
	final public function auth_changePassword($password){
		$hash = new hash();
		$passHash = $hash->cr_generatePasswordHash($password,16);
		
	}

	/*!
	 * This function gets the password change status
	 *
	 * @author Kevin Wijesekera
	 * @date 3-27-2015
	 *
	 * @param $password Password to change
	 *
	 * @return true if password can be changed
	 */
	final public function auth_canChangePassword(){
		return true;
	}
}
