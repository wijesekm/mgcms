<?php
/*!
 * @file		ldap.abstract.php
 * @author 		Kevin Wijesekera
 * @copyright 	2015
 * @edited		3-17-2015
 * @section DESCRIPTION
 * This file contains the ldap which is designed to
 * provide ldap related functions.
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

/*! LDAP class */
class ldap{
	
	//protected vars
	protected $db;			/*!< Database access object */
	protected $bind;		/*!< Bind access object */
	protected $cfg;			/*!< Database configuration */
	protected $debug;		/*!< Enable Debug */
	
	//private

	/* ######################
	 * PUBLIC FUNCTIONS + CONSTRUCTORS
	 * ######################
	 */
	 final public function __construct(){
		$this->debug = $debug;
		$this->db = false;
		$this->bind = false;
		$this->cfg = array(
			'logging' => false,
			'tls'=>''
			'options'=>array()
		);
	 }
	 
	final public function db_setConfig($item,$value){
		$this->cfg[$item] = $value;
	}
	 
	final public function db_connect($host,$port,$user,$password){
		if($this->db){
			trigger_error('(ldap): Connection already established',E_USER_NOTICE);
			return false;
		}

		$this->db = ldap_connect($host,$port);
		if(!$this->db){
			trigger_error('(ldap): Could not bind to server',E_USER_WARNING);
			return false;
		}
		
		foreach($this->cfg['options'] as $key=>$val){
			if(!ldap_set_option($this->db, $key, $val)){
				trigger_error('(ldap): Could not set option',E_USER_WARNING);
			}
		}
		
		if ($this->cfg['tls']) {
            ldap_start_tls($this->db);
        }
		
		if(!$this->bind = ldap_bind($this->db,$user,$password)){
			trigger_error('(ldap): Could not bind with user account',E_USER_WARNING);
			return false;
		}
	}

	final public function db_close(){
		if(!$this->db){
			return false;
		}
		if(!ldap_close($this->db)){
			trigger_error('(ldap): Could not close connection.'.$this->db_getLastError(),E_USER_WARNING);
			return false;
		}
		return true;
	}

	final public function db_getLastError(){
		return '#'.ldap_errno($this->db).' '.ldap_error($this->db);
	}
	
	final public function db_query($query_data){
		
	}
}
