<?php
/*!
 * @file		acl.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-13-2015
 * @section DESCRIPTION
 * This file contains the acl_admin class which has functions
 * for administering the acl.
 *
 * The following ACL types are supported by default:
 * access - Allows access to page
 * full - Full control (bypass all other ACl's)
 *
 * The system also supports custom ACL's for each page.  This is where
 * the meat of the program is.  Based on previous work the standard
 * update/delete ACL's were not cutting it.
 *
 * The following are valid ACL values with the exception of full which is a bool
 * 3 Grant ACL override (overrides all)
 * 2 Deny ACL override (overrides 1's)
 * 1 Grant ACL
 * 0 Deny ACL
 
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

/*! ACL Class */
class acl_admin{
	
	//public vars
	
	//private vars

	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	
	/*!
	 * This is the class constructor which sets up the log file names
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 */
	public function __construct(){

	}
	
	/*!
	 * This function gets all records for a group
	 * out of the database.
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @param $group Group to get records
	 *
	 * @return array of group data
	 */
	public function acl_getRecords($group){
		$conds = array(array(false,false,'acl_group','=',$group));
		return $GLOBALS['MG']['SQL']->sql_fetchResult(array(TABLE_PREFIX.'acl'),false,$conds);
	}
	
	/*!
	 * This function updates a record in the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @param $group Group to update
	 * @param $page Page to update
	 * @param $acl Packed ACL list (permission=>value)
	 *
	 * @return true on success, false on failure
	 */
	public function acl_updateRecord($group,$page,$acl){
		if(!is_array($record) || count($record) == 0){
			return;
		}
		$conds=array(array(false,array(DB_AND),'acl_group','=',$group),array(false,false,'acl_page','=',$page));
		$upd = array(
			array('access',0),
			array('custom',''),
			array('full',false)
		);
		$this->acl_unpack($acl,$insert[0][1],$insert[1][1],$insert[2][1]);
		
		if(!$GLOBALS['MG']['SQL']->sql_dataCommands(DB_UPDATE,array(TABLE_PREFIX.'acl'),$conds,$upd)){
			trigger_error('(acl_admin): Could not update row into database!',E_USER_WARNING);
			return false;
		}
		return true;
	}
	
	/*!
	 * This function inserts a record in the database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @param $group Group to add with
	 * @param $page Page to add with
	 * @param $acl Packed ACL list (permission=>value)
	 *
	 * @return true on success, false on failure
	 */
	public function acl_insertRecord($group,$page,$acl){
		$rows = array('acl_group','acl_page','access','custom','full');
		$insert = array($group,$page,0,'',false);
		$this->acl_unpack($acl,$insert[2],$insert[3],$insert[4]);
		
		if(!$GLOBALS['MG']['SQL']->sql_dataCommands(DB_INSERT,array(TABLE_PREFIX.'acl'),$rows,$insert)){
			trigger_error('(acl_admin): Could not insert row into database!',E_USER_WARNING);
			return false;
		}
		return true;
	}
	
	/*!
	 * This function unpacks an ACL array
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @param $acl Packed ACL array
	 * @param $access Store for access permission
	 * @param $custom Store for custom permissions
	 * @param $full Store for full permissions
	 */
	private function acl_unpack($acl,&$access,&$custom,&$full){
		$access = $acl['access'];
		$full = $acl['full'];
		$custom='';
		unset($acl['access']);
		unset($acl['full']);
		foreach($acl as $key=>$val){
			$custom .= $key.'|'.$val.';';
		}
	}
}
