<?php
/*!
 * @file		sqlact_admin.abstract.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-21-2015
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
class sqlact_admin extends account_admin{

	/*!
	 * This function queries the system for data
	 *
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $user_q User ID to search for or an exact load
	 */
	final public function act_users($user_q=false,$fields=false,$extended=false){
		if(!empty($user_q)){
			$user_q = array(array(DB_LIKE,'act_id','=',str_replace('*','%',$user_q)));
		}
		
		if(is_array($fields)){
			$t = $fields;
			$fields = array();
			foreach($t as $val){
				$fields[$val]=false;
			}
		}
		else{
			$fields = false;
		}
		
		$table = (!empty($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL']))?$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL']:TABLE_PREFIX.'accounts';
		$table_extras = (!empty($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL']))?$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL'].'_extra':TABLE_PREFIX.'accounts_extra';

		$query = array();
		$query[] = array(
			'type'=>DB_SELECT,
			'table'=>$table,
			'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
			'fields'=>$fields,
			'conds'=>$user_q
		);
		
		if($extended){
			$query[] = array(
				'type'=>DB_SELECT,
				'table'=>$table_extras,
				'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
				'conds'=>$user_q
			);
		}
		
		$r = $GLOBALS['MG']['DB']->db_query($query);

		$data = array();
		if(!$r[0]['done']){
			return false;
		}
		
		foreach($r[0]['result'] as $val){
			$val['fullname']=explode(';',$val['fullname']);
			$data[$val['act_id']] = $val;
		}
		
		if(isset($r[1])){
			if(!$r[1]['done']){
				return false;
			}
			foreach($r[1]['result'] as $val){
				$data[$val['act_id']] = array_merge($data[$val['act_id']],$val);
			}
		}
		return $data;
	}
	
	final public function act_groups($group_q=false,$extended=false){
		$table_groups = (!empty($GLOBALS['MG']['CFG']['SITE']['GROUP_TBL']))?$GLOBALS['MG']['CFG']['SITE']['GROUP_TBL'].'_membership':TABLE_PREFIX.'groups_membership';
		
		if(!empty($group_q)){
			$group_q = array(array(DB_LIKE,'group_id','=',str_replace('*','%',$group_q)));
		}
		else{
			$group_q=false;
		}

		$query[] = array(
			'type'=>DB_SELECT,
			'table'=>$table_groups,
			'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
			'conds'=>$group_q
		);
		$query[] = array(
			'type'=>DB_SELECT,
			'table'=>$table_groups,
			'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
			'conds'=>array(
				array(DB_STD,'act_id','=','*'),
				DB_OR,
				array(DB_LIKE,'act_id','=','-'.$group_q)
			)
		);
		$r = $GLOBALS['MG']['DB']->db_query($query);
		if(!$r[0]['done']){
			return false;
		}
		$data=array();
		foreach($r[0]['result'] as $val){
			if(!isset($data[$val['group_id']])){
				$data[$val['group_id']]=array('users'=>array());
			}
			$data[$val['group_id']]['users'][$val['act_id']] = (int)$val['admin'];
		}
		foreach($r[1]['result'] as $val){
			if(isset($data[$val['group_id']])){
				$data[$val['group_id']]['users'][$val['act_id']] = (int)$val['admin'];
			}
		}
		return $data;
	}
	
}