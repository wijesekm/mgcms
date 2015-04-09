<?php
/*!
 * @file		mail_router.class.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		4-2-2015
 * @section DESCRIPTION
 * This file contains the class which is charged with routing mail
 * for the system.
 *
 * The system supports batch send as well as instantaneous sending of mail.
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

/*!< MAIL_ROUTER class */
class mail_router{

	private $mail_queue;	/*!< Queue of all messages to send */
	private $mail_len;		/*!< Queue Length */
	
	/*!
	 * This is the class constructor which sets up the mailer system
	 *
	 * @author Kevin Wijesekera
	 * @date 4-2-2015
	 *
	 * @param $groups Array of groups user belongs to
	 */
	public function __construct(){
		$this->mail_queue = array();
		$this->mail_len = 0;
	}

	/*!
	 * This function adds a message to the queue
	 *
	 * @author Kevin Wijesekera
	 * @date 4-2-2015
	 *
	 * @param $message Message to push to queue
	 * @param $application Application to send mail as
	 * @param $class Application class to send mail as
	 */
	public function m_queue($message,$application,$class){
		if(!is_array($this->mail_queue[$application])){
			$this->mail_queue[$application] = array();
		}
		if(!is_array($this->mail_queue[$application][$class])){
			$this->mail_queue[$application][$class] = array();
		}
		$this->mail_queue[$application][$class][] = $message;
		$this->mail_len++;
	}

	/*!
	 * This function processes the mail queue
	 *
	 * @author Kevin Wijesekera
	 * @date 4-2-2015
	 *
	 */
	public function m_processQueue(){
		if($this->mail_len == 0){
			return;
		}
        
        //load packages needed
        mginit_load(array(
            array('email','mail','abstract'),
            array('email',$GLOBALS['MG']['CFG']['MAIL']['METHOD'],'class'),
            array('email','mail_encoding','class')
        ));
        
        //get mailer configuration data
		$data = $GLOBALS['MG']['DB']->db_query(array(
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.'mail_classes',
				'fields'=>array(
					'pkg_id'=>false,
					'class_id'=>false,
					'can_batch'=>false,
					'can_ignore'=>false
				),
				'conds'=>false
			),
			array(
				'type'=>DB_SELECT,
				'table'=>TABLE_PREFIX.'mail_usercfg',
				'conds'=>false
			)
		));
		if(!$data[0]['done'] || !$data[1]['done']){
			trigger_error('Could not load mail routing configuration',E_USER_ERROR);
			return;
		}
		$cfg_base=array();
		foreach($data[0]['result'] as $val){
			if(isset($val['pkg_id'])){
				if(!isset($cfg_base[$val['pkg_id']])){
					$cfg_base[$val['pkg_id']] = array();
				}
				$cfg_base[$val['pkg_id']][$val['class_id']] = array('cfgb'=>(bool)$val['can_batch'],'cfgi'=>(bool)$val['can_ignore'],'b'=>false,'i'=>false);
			}
		}
		$cfg_user=array();
		foreach($data[1][$result] as $val){
			if(isset($val['pkg_id'])){
				if(!isset($cfg_user[$val['act_id']])){
					$cfg_user[$val['act_id']] = $cfg_base;
				}
				if($cfg_user[$val['act_id']][$val['pkg_id']][$val['class_id']]['cfgb']){
					$cfg_user[$val['act_id']][$val['pkg_id']][$val['class_id']]['b'] = (bool)$val['batch_send'];
				}
				if($cfg_user[$val['act_id']][$val['pkg_id']][$val['class_id']]['cfgi']){
					$cfg_user[$val['act_id']][$val['pkg_id']][$val['class_id']]['i'] = (bool)$val['ignore'];
				}
			}
		}
		$insert_queue=array();
        $mailer = new $GLOBALS['MG']['CFG']['MAIL']['METHOD']();
		foreach($this->mail_queue as $application=>$appData){
			foreach($appdata as $class=>$message){
				//$this->mail_queue[$application][$class]
				//to, cc, bcc, sender, reply to, subject, message
				
				//remove ignore users and set batch users
				$message['batch']=array();
				foreach($message['to'] as $val){
					if(isset($cfg_user[$val])){
						if($cfg_user[$val][$application][$class]['i']){
							unset($message['to'][$val]);
						}
						if($cfg_user[$val][$application][$class]['b']){
							unset($message['to'][$val]);
							$message['batch'][] = $val;
						}
					}
				}
				foreach($message['cc'] as $val){
					if(isset($cfg_user[$val])){
						if($cfg_user[$val][$application][$class]['i']){
							unset($message['cc'][$val]);
						}
						if($cfg_user[$val][$application][$class]['b']){
							unset($message['cc'][$val]);
							$message['batch'][] = $val;
						}
					}
				}
				
				//send message
                $mailer->mail_parse($message);
                $mailer->mail_send();
				
				//batch message
				unset($message['cc']);
				unset($message['to']);
				if(count($messages['batch']) > 0){
					$insert_queue[] = json_encode($message);
				}
			}
		}
	}
	
	public function m_processCron(){
		
	}
}
