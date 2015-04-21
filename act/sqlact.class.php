<?php
/*!
 * @file		sqlact.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-20-2015
 * @section DESCRIPTION
 * This file contains the sqlact class which impliments SQL based
 * accounts.  SQL accounts use two tables, one for the base data and one
 * for any extra rows.
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

/*! SQL Account Class */
class sqlact extends account{
		
	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */
	
	/*!
	 * This function loads the account, groups and ACL
	 * out of a sql database
	 *
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @return true if user is loaded, false if not
	 */
	final protected function act_load($user){
		$this->is_auth = false;
		$this->is_loaded = false;
		$table = (!empty($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL']))?$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL']:TABLE_PREFIX.'accounts';
		$table_extras = (!empty($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL']))?$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_TBL'].'_extra':TABLE_PREFIX.'accounts_extra';
		$table_groups = (!empty($GLOBALS['MG']['CFG']['SITE']['GROUP_TBL']))?$GLOBALS['MG']['CFG']['SITE']['GROUP_TBL'].'_membership':TABLE_PREFIX.'groups_membership';
		if(empty($user)){
			$user = $GLOBALS['MG']['CFG']['SITE']['DEFAULT_ACT'];
		}
		
		if(empty($user)){
			trigger_error('(sqlact): No account to load',E_USER_ERROR);
			return;
		}
		
		if($user != $GLOBALS['MG']['CFG']['SITE']['DEFAULT_ACT']){
			$this->is_auth = true;
		}
		
		$query = array(
			array(
				'type'=>DB_SELECT,
				'table'=>$table,
				'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
				'conds'=>array(
					array(DB_STD,'act_id','=',$user)
				)
			),
			array(
				'type'=>DB_SELECT,
				'table'=>$table_extras,
				'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
				'conds'=>array(
					array(DB_STD,'act_id','=',$user)
				)
			),
			array(
				'type'=>DB_SELECT,
				'table'=>$table_groups,
				'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
				'conds'=>array(
					array(DB_STD,'act_id','=',$user),
					DB_OR,
					array(DB_STD,'act_id','=','*')
				)
			)
		);
		
		$data = $GLOBALS['MG']['DB']->db_query($query);
		if(!$data[0]['done'] || !$data[1]['done'] || !$data[2]['done']){
			trigger_error('(sqlact): Could not load account from accounts table',E_USER_ERROR);
			return false;
		}
		$this->user_data = $data[0]['result'][0];
		$this->user_data['id']=$this->user_data['act_id'];
		$this->user_data['groups']=array();
		
		foreach($data[1]['result'] as $val){
			if(!empty($val['extra_column'])){
				$this->user_data[$val['extra_column']] = $val['value'];
			}
		}
		
		foreach($data[2]['result'] as $val){
			if(!empty($val['group_id'])){
				$this->user_data['groups'][$val['group_id']] = mg_toBool($val['admin']);
			}
		}
		
		$this->acl = new acl($this->user_data['groups']);
		$this->is_loaded = true;
		return true;
	}
}
