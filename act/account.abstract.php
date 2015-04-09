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

/*! Accounts Abstract Class */
abstract class account{
	//public vars
	public $acl;			/*!< ACL Structure */
	
	//protected vars
	protected $user_data;		/*!< Data for loaded users */
	protected $is_auth;			/*!< User is authenticated */
	protected $is_loaded;		/*!< User has been loaded */
	protected $is_impersonated;	/*!< True of the user is being impersonated */

	/*!
	 * This is the class constructor which loads user, group and ACL data
	 *
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $user UID to load into the system
	 */
	final public function __construct($user){
		$this->act_load($user);
	}
	
	/*!
	 * This function gets a piece of user data
	 *
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $data_item Data item to get 
	 *
	 * @return Value of data item or false if no item found
	 */
	final public function act_get($data_item){
		switch($data_item){
			case 'language':
				return (empty($this->user_data['language']) || !$GLOBALS['MG']['SITE']['LANG_ALLOW_OVERRIDE'])?$GLOBALS['MG']['CFG']['SITE']['DEFAULT_LANG']:$this->user_data['language'];
			break;
			default:
				return (isset($this->user_data[$data_item])?$this->user_data[$data_item]:false);
			break;
		}
		
	}
	
	/*!
	 * This function sets data for the current
	 * session only.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $data_item Data item to store
	 * @param $val Value to store
	 */
	final public function act_set($data_item,$val){
		$this->user_data[$data_item] = $val;
	}
	
	/*!
	 * This function sets the current session as an impersonation session.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @return true if user is loaded, false if not
	 */
	final public function act_setImpersonate($impersonate_uid){
		$this->is_impersonated = true;
		
		if(!$this->acl->acl_check('*','full')){
			trigger_error('(account): Cannot start impersonate session, Permission Denied',E_USER_WARNING);
			return false;
		}
		
		$root_user = array(
			'id'=>$this->user_data['id'],
			'fullname'=>$this->user_data['fullname'],
			'email'=>$this->user_data['email']
		);
		
		return $this->act_load($impersonate_uid);
	}
	
	/*!
	 * This function checks to see if a user account is loaded
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @return true if user is loaded, false if not
	 */
	final public function act_isLoaded(){
		return $is_loaded;
	}
	
	/*!
	 * This function checks the user to see if its
	 * the guest user.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @return true if user is authenticated, false if account is guest
	 */
	final public function act_isAuth(){
		return $this->is_auth;
	}
	
	abstract protected function act_load($user);
}
