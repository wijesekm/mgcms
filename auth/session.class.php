<?php
/*!
 * @file		session.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-20-2015
 * @section DESCRIPTION
 * This file contains the session class which has functions for
 * starting, stopping, checking, refreshing and getting sessions
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

/*! Session Class */
class session{
	//Public vars
	public $stime;		/*!< Time to use for session stuff */
	
	//Private Vars
	private $table;		/*!< Table to draw session data out of */
	private $length;	/*!< Session length */
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
	public function __construct($t){
		if(isset($GLOBALS['MG']['CFG']['SITE']['ACCOUNTS_SESSION_TBL'])){
			$this->table=$GLOBALS['MG']['CFG']['SITE']['ACCOUNTS_SESSION_TBL'];
		}
		else{
			$this->table=TABLE_PREFIX.'sessions';
		}
		
		$this->stime = $t;
		
		if(empty($GLOBALS['MG']['CFG']['SITE']['COOKIE_PATH']) || empty($GLOBALS['MG']['CFG']['SITE']['COOKIE_DOM']) || !isset($GLOBALS['MG']['CFG']['SITE']['COOKIE_SECURE'])
			|| empty($GLOBALS['MG']['CFG']['SITE']['SESSION_COOKIE'])){
			trigger_error('(session): Not all configuration defined',E_USER_ERROR);
		}
	}
	
	/*!
	 * This function starts a new session
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $uid User ID to start session with
	 * @param $length Length of session or 0 for on browser close
	 *
	 * @return false on failure or session ID string
	 */
	public function ses_new($uid,$length=0){
		if(empty($uid)){
			return false;
		}
		$sid = md5(uniqid(rand(),true));
	
		if(!$this->ses_dbCommand(0,$uid,$sid,$length)){
			return false;
		}
	
		if(!$this->ses_setCookies($uid,$sid,$length)){
			return false;
		}
		return $sid;
	}
	
	/*!
	 * This function checks to see if a session is valid
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $uid User ID to check
	 * @param $sid Session ID to check
	 *
	 * @return false on mismatch, true on match
	 */
	public function ses_check($uid,$sid){
		if(empty($uid) || empty($sid)){
			return false;
		}
		$data = $this->ses_dbCommand(3,false,$sid);
		if(!is_array($data)){
			return false;
		}
		$data = $data[0];
		if(!isset($data['user'])){
			return false;
		}
		
		$this->length = $data['user'];
		
		if($uid === $data['user'] && $sid === $data['ses_id']){
			if(!$GLOBALS['MG']['CFG']['SITE']['TWO_FACTOR_EN']  || (bool)$data['twofactor']){
				return true;
			}
		}
		return false;
	}
	
	/*!
	 * This function updates a sessions refresh time
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $uid User ID to refresh
	 * @param $sid Session ID to refresh
	 * @param $length Refresh session length or 0 for browser close 
	 *
	 * @return false on failure, true on success
	 */
	public function ses_update($uid,$sid){
		if(empty($uid) || empty($sid)){
			return false;
		}
		if(!$this->ses_dbCommand(2,$uid,$sid)){
			return false;
		}
	
		if(!$this->ses_setCookies($uid,$sid,$this->length)){
			return false;
		}
		return true;
	}
	
	/*!
	 * This function closes a session
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $sid Session ID to close
	 *
	 * @return false on failure, true on success
	 */
	public function ses_remove($sid,$cookies=true){
		if(empty($sid)){
			return false;
		}
		if(!$this->ses_dbCommand(1,false,$sid)){
			return false;
		}
		if($cookies){
			if(!$this->ses_setCookies('','',-86400)){
				return false;
			}
		}
		return true;
	}
	
	/*!
	 * This function gets session data
	 *
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $uid User ID to load or blank for all load
	 *
	 * @return data array
	 */
	public function ses_get($uid=false){
		$data = $this->ses_dbCommand(3,$uid,false);
		if(is_array($data)){
			return $data;
		}
		return array();
	}
	
	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */	
	
	/*!
	 * This function runs any database queries for the sessions interface
	 * 
	 * Commands
	 * 0 - Start
	 * 1 - Stop
	 * 2 - Update
	 * other - Get Data
	 * @author Kevin Wijesekera
	 * @date 3-20-2015
	 *
	 * @param $cmd Command
	 * @param $uid User ID
	 * @param $sid Session ID
	 * @param $length Length of session
	 *
	 * @return false on failure, true or data on success
	 */
	private function ses_dbCommand($cmd,$uid,$sid,$length=0){
		$query = array();
		switch($cmd){
			case 0:
				$query[] = array(
					'table'=>$this->table,
					'type'=>DB_INSERT,
					'cols'=>array('ses_id','user','started','renewed','length','last_ip'),
					'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
					'rows'=>array(
						array($sid,$uid,$this->stime,$this->stime,$length,$_SERVER['REMOTE_ADDR'])
					)
				);
			break;
			case 1:
				$query[] = array(
					'table'=>$this->table,
					'type'=>DB_DELETE,
					'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
					'conds'=>array(
						array(DB_STD,'ses_id','=',$sid)
					),
				);
			break;
			case 2:
				$query[] = array(
					'table'=>$this->table,
					'type'=>DB_UPDATE,
					'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
					'conds'=>array(
						array(DB_STD,'ses_id','=',$sid)
					),
					'changes'=>array(
						array('renewed',$this->stime),
						array('last_ip',$_SERVER['REMOTE_ADDR'])
					)
				);
			break;
			default:
				$conds = false;
				if(empty($sid)){
					if(!empty($uid)){
						$conds = array(array(DB_STD,'user','=',$uid));
					}
				}
				else{
					$conds=array(array(DB_STD,'ses_id','=',$sid));
				}
				$query[] = array(
					'table'=>$this->table,
					'type'=>DB_SELECT,
					'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],
					'conds'=>$conds
				);
			break;
		};

		$r = $GLOBALS['MG']['DB']->db_query($query);

		if(!$r[0]['done']){
			trigger_error('(session): Could not perform database operation: '.$val['error'],E_USER_WARNING);
			return false;
		}
		
		return isset($r[0]['result'])?$r[0]['result']:$r[0]['done'];
	}
	
	/*!
	 * This sets session cookies
	 * 
	 * @author Kevin Wijesekera
	 * @date 3-13-2015
	 *
	 * @param $uid User ID
	 * @param $sid Session ID
	 * @param $length Length of session
	 *
	 * @return false on failure, true or data on success
	 */
	private function ses_setCookies($uid,$sid,$length){
		if($length!=0){
			$length+=$this->stime;
		}
		$d = $GLOBALS['MG']['CFG']['SITE'];
		$GLOBALS['MG']['CFG']['SITE']['COOKIE_SECURE'] = false;
		$GLOBALS['MG']['CFG']['SITE']['COOKIE_PATH'] = '/';
		$GLOBALS['MG']['CFG']['SITE']['COOKIE_DOM'] = 'localhost';
		$GLOBALS['MG']['CFG']['SITE']['SESSION_COOKIE'] = 'auth_';
		
		$t = setcookie($GLOBALS['MG']['CFG']['SITE']['SESSION_COOKIE'].'token',$sid,$length,$d['COOKIE_PATH'],$d['COOKIE_DOM'],$d['COOKIE_SECURE']);
		$t &= setcookie($GLOBALS['MG']['CFG']['SITE']['SESSION_COOKIE'].'user',$uid,$length,$d['COOKIE_PATH'],$d['COOKIE_DOM'],$d['COOKIE_SECURE']);
		
		if(!$t){
			trigger_error('(session): Could not set session cookies',E_USER_ERROR);
		}
		return $t;
	}
}
