<?php
/*!
 * @file		twofactor.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-1-2015
 * @section DESCRIPTION
 * This file contains the class which handles two factor authentication
 *
 * Currently, the two factor auth supports cell based authenication as well as
 * email based
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
class twofactor{
	
	//public vars
	
	//private vars
	private $ctime;		/*!< Current time */
	private $table;		/*!< Table name */
	
	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */

	/*!
	 * This is the class constructor which sets up some basic information
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $groups Array of groups user belongs to
	 */
	public function __construct($time){
		$this->ctime = $time;
		$this->table = (isset($GLOBALS['MG']['CFG']['SITE']['TWO_FACTOR_TBL']))?$GLOBALS['MG']['CFG']['SITE']['TWO_FACTOR_TBL']:TABLE_PREFIX.'twofactor';
	}

	/*!
	 * This function checks an entered token against
	 * the database
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $token Token to check
	 * @param $user User account to check
	 * @param $ip IP address to check
	 *
	 * @return true on match, false otherwise
	 */
	public function tf_check($token,$user,$ip){
		$query = array();
		$index = 0;
		if(!empty($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'])){
			$query[] = array(
				'type'=>DB_SELECT_DATABASE,
				'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB']
			);
			$index = 1;
		}
		mg_changeDb($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],false,$query);
		$query[] = array(
			'type'=>DB_DELETE,
			'table'=>$this->table,
			'conds'=>array(
				array(
					DB_STD,
					'issued',
					'<',
					($this->ctime - 7200)  //2 hour expiration
				)
			)
		);
		$query[]= array(
			'type'=>DB_SELECT,
			'table'=>$this->table,
			'conds'=>array(
				array(
					DB_STD,
					'act_id',
					'=',
					$user
				),
				DB_AND,
				array(
					DB_STD,
					'tf_ip',
					'=',
					$ip
				)
			)
		);
		mg_changeDb($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],true,$query);
		$data = $GLOBALS['MG']['DB']->db_query($query);
		
		if(!$data[$index]['done'] || !$data[$index+1]['done']){
			trigger_error('(twofactor): Could not query database '.$data[$index]['error'].$data[$index+1]['error'],E_USER_ERROR);
			return false;
		}

		$r = $data[$index+1]['result'];
		if(!isset($r[0])){
			return;
		}
		if($r[0]['tf_ip'] === $ip && $r[0]['act_id'] === $user && $token === $r[0]['code']){
			return true;
		}
		return false;
	}

	/*!
	 * This function generates a new two factor code and sends
	 * it to the user.
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $user User account to check
	 * @param $ip IP address to check
	 *
	 * @return true on success, false on failure
	 */
	public function tf_generateCode($user,$ip){
		$query = array();
		$index = 0;
		if(!empty($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'])){
			$query[] = array(
				'type'=>DB_SELECT_DATABASE,
				'db'=>$GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB']
			);
			$index = 1;
		}
		mg_changeDb($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],false,$query);
		$query[] = array(
			'type'=>DB_DELETE,
			'table'=>$this->table,
			'conds'=>array(
				array(
					DB_STD,
					'act_id',
					'=',
					$user
				),
				DB_AND,
				array(
					DB_STD,
					'tf_ip',
					'=',
					$ip
				)
			)
		);
		$key = $this->tf_genhotp(16,6);
		$query[]= array(
			'type'=>DB_INSERT,
			'table'=>$this->table,
			'cols'=>array('act_id','tf_ip','code','issued'),
			'rows'=>array(
				array($user,$ip,$key,$this->ctime);
			)
		);
		mg_changeDb($GLOBALS['MG']['CFG']['SITE']['ACCOUNT_DB'],true,$query);
		$data = $GLOBALS['MG']['DB']->db_query($query);
		
		if(!$data[$index]['done'] || !$data[$index+1]['done']){
			trigger_error('(twofactor): Could not query database '.$data[$index]['error'].$data[$index+1]['error'],E_USER_ERROR);
			return false;
		}
		
		//email code
		$email = $GLOBALS['MG']['USER']->act_get('email');
		if($GLOBALS['MG']['USER']->act_get('two_factor')==2){
			$email = $GLOBALS['MG']['USER']->act_get('phone').$GLOBALS['MG']['USER']->act_get('phone_carrier');
		}
		$GLOBALS['MG']['MAIL']->m_queue(array(
			'to'=>array($email),
			'subject'=>'',
			'message'=>''
		),'twofactor','base');
		
		return true;
	}
	
	/* ######################
	 * PRIVATE FUNCTIONS
	 * ######################
	 */
	 
	/*!
	 * This function generates a new two factor code to be sent to the
	 * user
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $key_len Length of private key to be used
	 * @param $length Length of generated code
	 *
	 * @return code 
	 */
    private function tf_genhotp($key_len,$length){
        if($key_len < 8){
            trigger_error('(twofactor): Key length is too short to gen hotp',E_USER_WARNING);
        }
        if($length < 4){
            trigger_error('(twofactor):Length is too short to gen hotp',E_USER_WARNING); 
        }
        $key = $this->tf_genKey();
        $counter = $this->ctime/30;
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1',$bin,$key,true);
        return str_pad($this->tf_truncate($hash,$length),$length,'0',STR_PAD_LEFT);
    }

	/*!
	 * This function truncates a hash to a length
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $hash Hash to truncates
	 * @param $length Length of new hash
	 *
	 * @return truncated hash 
	 */
    private function tf_truncate($hash,$length){
	    $offset = ord($hash[19]) & 0xf;
	    return (
	        ((ord($hash[$offset+0]) & 0x7f) << 24 ) |
	        ((ord($hash[$offset+1]) & 0xff) << 16 ) |
	        ((ord($hash[$offset+2]) & 0xff) << 8 ) |
	        (ord($hash[$offset+3]) & 0xff)
	    ) % pow(10, $length);
    }

	/*!
	 * This function generates a random private key
	 *
	 * @author Kevin Wijesekera
	 * @date 4-1-2015
	 *
	 * @param $length Length of new key
	 *
	 * @return generated key 
	 */
    private function tf_genKey($length = 16){
        $characters = '234567QWERTYUIOPASDFGHJKLZXCVBNM';
        $ret = '';
        for($i=0;$i<$length;$i++){
            $ret.= $characters[rand(0,31)];
        }
        return $ret;
    }
}
