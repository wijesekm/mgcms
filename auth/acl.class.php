<?php
/*!
 * @file		acl.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-13-2015
 * @section DESCRIPTION
 * This file contains the acl class which has functions for
 * getting, checking and updating ACL on the system.
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
class acl{
	
	//public vars
	
	//private vars
	private $acl_list;	/*!< List of all user ACL by page */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */

	/*!
	 * This is the class constructor which loads the ACL data into the system
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $groups Array of groups user belongs to
	 */
	public function __construct($groups){
		//get data from DB
		$conds=array();
		$first = false;
		if(!is_array($groups) || count($groups) == 0){
			$this->acl_list = array();
			return;
		}
		foreach($groups as $key=>$val){
			if($first){
				$conds[] = DB_OR;
			}
			$first = true;
			$conds[]=array(DB_STD,'acl_group','=',$key);
			$t = strpos($key,'-');
			if($t !== false){
				$conds[] = DB_OR;
				$conds[] = array(DB_LIKE,'acl_group','=','*-'.substr($key,$t).'%');
			}
		}
		$data = $GLOBALS['MG']['DB']->db_query(array(
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.'acl',
				'conds'=>$conds
			)
		));
		
		if(!$data[0]['done']){
			trigger_error('(acl): Could not load ACL from database '.$data[0]['error'],E_USER_ERROR);
			return;
		}

		foreach($data[0]['result'] as $acl){
			if(!empty($acl['acl_page'])){

				if(!isset($this->acl_list[$acl['acl_page']])){
					$this->acl_list[$acl['acl_page']] = array(
						'full'=>0,
						'access'=>0,
					);
				}

				if($acl['full']){
					$this->acl_list[$acl['acl_page']] = array(
						'full'=>1,
						'access'=>1,
					);
				}

				if(!$this->acl_list[$acl['acl_page']]['full']){	
					//process access
					$acl['access'] = (int)$acl['access'];
					if($acl['access'] == 2){
						$this->acl_list[$acl['acl_page']]['access'] = 2;
					}
					else if($acl['access'] == 1 && $this->acl_list[$acl['acl_page']]['access'] != 2){
						$this->acl_list[$acl['acl_page']]['access'] = 1;
					}
					
					//parse custom ACLs (acl|val;)
					if(!empty($acl['custom']) && strpos($acl['custom'],';') != 0){
						preg_match_all('/(.*?)\|([01234]);/',$acl['custom'],$tmp);
						$s = count($tmp[0]);
						for($i=0;$i<$s;$i++){
							if(!empty($tmp[1][$i])){
								if(!isset($this->acl_list[$acl['acl_page']][$tmp[1][$i]])){
									$this->acl_list[$acl['acl_page']][$tmp[1][$i]] = 0;
								}
								$v = (int)$tmp[2][$i];
								if($v == 2){
									$this->acl_list[$acl['acl_page']][$tmp[1][$i]] = 2;
								}
								else if($v == 1 && $this->acl_list[$acl['acl_page']][$tmp[1][$i]] != 2){
									$this->acl_list[$acl['acl_page']][$tmp[1][$i]] = 1;
								}
							}
						}
					}
				}
			}
		}
	}
	
	/*!
	 * This function performs a permissions check to see
	 * if an access level for a page is granted
	 *
	 * @author Kevin Wijesekera
	 * @date 3-16-2015
	 *
	 * @param $page Page to check ACL on
	 * @param $level Access level to check
	 *
	 * @return false if no entry or no access, true if access
	 */
	public function acl_check($page,$level='access'){
		if(isset($this->acl_list['*'])){
			if($this->acl_list['*']['full']){
				return true;
			}
			return $this->acl_list['*'][$level]===1;
		}
		if(!isset($this->acl_list[$page]) || ! isset($this->acl_list[$page][$level])){
			return false;
		}
		if($this->acl_list[$page]['full']){
			return true;
		}
		return $this->acl_list[$page][$level]===1;
	}
}
